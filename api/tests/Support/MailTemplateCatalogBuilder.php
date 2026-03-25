<?php

namespace Tests\Support;

use Fleetbase\FleetOps\Flow\Activity;
use Fleetbase\FleetOps\Events\OrderDispatchFailed as CoreOrderDispatchFailedEvent;
use Fleetbase\FleetOps\Mail\CustomerCredentialsMail;
use Fleetbase\FleetOps\Models\Contact;
use Fleetbase\FleetOps\Models\Driver;
use Fleetbase\FleetOps\Models\Order;
use Fleetbase\FleetOps\Models\Waypoint;
use Fleetbase\FleetOps\Notifications\OrderAssigned;
use Fleetbase\FleetOps\Notifications\OrderCanceled;
use Fleetbase\FleetOps\Notifications\OrderCompleted;
use Fleetbase\FleetOps\Notifications\OrderDispatchFailed;
use Fleetbase\FleetOps\Notifications\OrderDispatched;
use Fleetbase\FleetOps\Notifications\OrderFailed;
use Fleetbase\FleetOps\Notifications\OrderSplit;
use Fleetbase\FleetOps\Notifications\WaypointCompleted;
use Fleetbase\Mail\TestMail;
use Fleetbase\Mail\UserCredentialsMail;
use Fleetbase\Mail\VerificationMail;
use Fleetbase\Models\Company;
use Fleetbase\Models\Invite;
use Fleetbase\Models\User;
use Fleetbase\Models\VerificationCode;
use Fleetbase\Notifications\PasswordReset;
use Fleetbase\Notifications\UserAcceptedCompanyInvite;
use Fleetbase\Notifications\UserCreated;
use Fleetbase\Notifications\UserForgotPassword;
use Fleetbase\Notifications\UserInvited;
use Fleetbase\Storefront\Mail\StorefrontNetworkInvite;
use Fleetbase\Storefront\Models\Network;
use Fleetbase\Storefront\Notifications\StorefrontOrderAccepted;
use Fleetbase\Storefront\Notifications\StorefrontOrderCanceled;
use Fleetbase\Storefront\Notifications\StorefrontOrderCompleted;
use Fleetbase\Storefront\Notifications\StorefrontOrderCreated;
use Fleetbase\Storefront\Notifications\StorefrontOrderDriverAssigned;
use Fleetbase\Storefront\Notifications\StorefrontOrderEnroute;
use Fleetbase\Storefront\Notifications\StorefrontOrderNearby;
use Fleetbase\Storefront\Notifications\StorefrontOrderPreparing;
use Fleetbase\Storefront\Notifications\StorefrontOrderReadyForPickup;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

class MailTemplateCatalogBuilder
{
    public function generate(string $outputPath): array
    {
        config()->set('app.name', 'KANTRACK');
        config()->set('fleetbase.console.host', 'kantrack.test');
        config()->set('fleetbase.console.secure', false);
        config()->set('fleetbase.console.subdomain', null);

        $definitions = $this->templateDefinitions();
        $discovered = $this->discoverCurrentInventory();
        $results = array_map(fn (array $definition) => $this->renderDefinition($definition), $definitions);

        $summary = [
            'total_templates' => count($definitions),
            'mailables' => count(array_filter($definitions, fn (array $definition) => $definition['kind'] === 'mailable')),
            'notifications' => count(array_filter($definitions, fn (array $definition) => $definition['kind'] === 'notification')),
            'rendered' => count(array_filter($results, fn (array $result) => $result['status'] === 'rendered')),
            'failed' => count(array_filter($results, fn (array $result) => $result['status'] === 'error')),
            'discovered_mailables' => count($discovered['mailables']),
            'discovered_notifications' => count($discovered['notifications']),
            'dedicated_mail_view_files' => count($discovered['view_files']),
        ];

        File::put($outputPath, $this->buildCatalogHtml($results, $summary, $discovered));

        return [
            'summary' => $summary,
            'results' => $results,
            'definitions' => $definitions,
            'discovered' => $discovered,
            'output_path' => $outputPath,
        ];
    }

    public function supportedTemplateClasses(): array
    {
        return array_values(array_map(fn (array $definition) => $definition['class'], $this->templateDefinitions()));
    }

    private function renderDefinition(array $definition): array
    {
        try {
            if ($definition['kind'] === 'mailable') {
                [$subject, $html] = $this->renderMailable($definition['factory']);
            } else {
                [$subject, $html] = $this->renderNotification($definition['factory']);
            }

            return [
                ...$definition,
                'status' => 'rendered',
                'subject' => $subject,
                'html' => $html,
                'error' => null,
            ];
        } catch (Throwable $error) {
            return [
                ...$definition,
                'status' => 'error',
                'subject' => null,
                'html' => null,
                'error' => $error::class . ': ' . $error->getMessage(),
            ];
        }
    }

    private function renderMailable(callable $factory): array
    {
        /** @var Mailable $mailable */
        $mailable = $factory();
        $subject = null;

        if (method_exists($mailable, 'envelope')) {
            $subject = data_get($mailable->envelope(), 'subject');
        }

        if ($subject === null && method_exists($mailable, 'build')) {
            $built = clone $mailable;
            $built->build();
            $subject = $built->subject;
        }

        return [$subject, (string) $mailable->render()];
    }

    private function renderNotification(callable $factory): array
    {
        [$notification, $notifiable] = $factory();

        /** @var MailMessage $mailMessage */
        $mailMessage = $notification->toMail($notifiable);

        return [$mailMessage->subject, (string) $mailMessage->render()];
    }

    private function templateDefinitions(): array
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'public_id' => 'user_admin',
            'name' => 'Ana Admin',
            'email' => 'ana.admin@kantrack.test',
            'phone' => '+593999000001',
        ], $company);
        $driverUser = $this->makeUser([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'public_id' => 'user_driver',
            'name' => 'Carlos Mena',
            'email' => 'carlos.mena@kantrack.test',
            'phone' => '+593999000002',
        ], $company);
        $customerContact = $this->makeCustomerContact($company, $driverUser);
        $storefront = $this->makeStorefront();
        $storefrontOrder = $this->makeStorefrontOrder($storefront);
        $fleetOpsOrder = $this->makeFleetOpsOrder();
        $waypoint = $this->makeWaypoint();
        $passwordResetCode = $this->makeVerificationCode($driverUser, '551122', 'password_reset');
        $verificationCode = $this->makeVerificationCode($driverUser, '482913', 'email_verification');
        $invite = $this->makeCompanyInvite($company, $admin, $driverUser);
        $networkInvite = $this->makeNetworkInvite($storefront, $admin);
        $activity = new Activity(['details' => 'Paquete recogido']);

        return [
            [
                'key' => 'core.mailable.verification',
                'package' => 'core-api',
                'kind' => 'mailable',
                'class' => VerificationMail::class,
                'label' => 'VerificationMail',
                'source' => 'fleetbase::mail.verification',
                'factory' => fn () => new VerificationMail($verificationCode),
            ],
            [
                'key' => 'core.mailable.user-credentials',
                'package' => 'core-api',
                'kind' => 'mailable',
                'class' => UserCredentialsMail::class,
                'label' => 'UserCredentialsMail',
                'source' => 'fleetbase::mail.user-credentials',
                'factory' => fn () => new UserCredentialsMail('TempPass!2026', $driverUser),
            ],
            [
                'key' => 'core.mailable.test-mail',
                'package' => 'core-api',
                'kind' => 'mailable',
                'class' => TestMail::class,
                'label' => 'TestMail',
                'source' => 'fleetbase::mail.test',
                'factory' => fn () => new TestMail($driverUser, 'smtp'),
            ],
            [
                'key' => 'fleetops.mailable.customer-credentials',
                'package' => 'fleetops-api',
                'kind' => 'mailable',
                'class' => CustomerCredentialsMail::class,
                'label' => 'CustomerCredentialsMail',
                'source' => 'fleetops::mail.customer-credentials',
                'factory' => fn () => new CustomerCredentialsMail('Customer!2026', $customerContact),
            ],
            [
                'key' => 'storefront.mailable.network-invite',
                'package' => 'storefront-api',
                'kind' => 'mailable',
                'class' => StorefrontNetworkInvite::class,
                'label' => 'StorefrontNetworkInvite',
                'source' => 'emails.storefront-network-invite',
                'factory' => fn () => new StorefrontNetworkInvite($networkInvite),
            ],
            [
                'key' => 'core.notification.user-invited',
                'package' => 'core-api',
                'kind' => 'notification',
                'class' => UserInvited::class,
                'label' => 'UserInvited',
                'source' => 'notifications::email',
                'factory' => fn () => [new UserInvited($invite), $this->makeNotifiable('Lucia Invitada')],
            ],
            [
                'key' => 'core.notification.user-forgot-password',
                'package' => 'core-api',
                'kind' => 'notification',
                'class' => UserForgotPassword::class,
                'label' => 'UserForgotPassword',
                'source' => 'notifications::email',
                'factory' => fn () => [new UserForgotPassword($passwordResetCode), $this->makeNotifiable('Carlos Mena')],
            ],
            [
                'key' => 'core.notification.user-created',
                'package' => 'core-api',
                'kind' => 'notification',
                'class' => UserCreated::class,
                'label' => 'UserCreated',
                'source' => 'notifications::email',
                'factory' => fn () => [new UserCreated($driverUser, $company), $this->makeNotifiable('Equipo Operaciones')],
            ],
            [
                'key' => 'core.notification.user-accepted-company-invite',
                'package' => 'core-api',
                'kind' => 'notification',
                'class' => UserAcceptedCompanyInvite::class,
                'label' => 'UserAcceptedCompanyInvite',
                'source' => 'notifications::email',
                'factory' => fn () => [new UserAcceptedCompanyInvite($company, $driverUser), $this->makeNotifiable('Equipo KANTRACK')],
            ],
            [
                'key' => 'core.notification.password-reset',
                'package' => 'core-api',
                'kind' => 'notification',
                'class' => PasswordReset::class,
                'label' => 'PasswordReset',
                'source' => 'notifications::email',
                'factory' => fn () => [new PasswordReset($passwordResetCode), $this->makeNotifiable('Carlos Mena')],
            ],
            [
                'key' => 'fleetops.notification.order-assigned',
                'package' => 'fleetops-api',
                'kind' => 'notification',
                'class' => OrderAssigned::class,
                'label' => 'OrderAssigned',
                'source' => 'notifications::email',
                'factory' => fn () => [new OrderAssigned($fleetOpsOrder), $this->makeNotifiable('Carlos Mena')],
            ],
            [
                'key' => 'fleetops.notification.order-canceled',
                'package' => 'fleetops-api',
                'kind' => 'notification',
                'class' => OrderCanceled::class,
                'label' => 'OrderCanceled',
                'source' => 'notifications::email',
                'factory' => fn () => [new OrderCanceled($fleetOpsOrder, 'El cliente solicito la cancelacion.', $waypoint), $this->makeNotifiable('Carlos Mena')],
            ],
            [
                'key' => 'fleetops.notification.order-completed',
                'package' => 'fleetops-api',
                'kind' => 'notification',
                'class' => OrderCompleted::class,
                'label' => 'OrderCompleted',
                'source' => 'notifications::email',
                'factory' => fn () => [new OrderCompleted($fleetOpsOrder, $waypoint), $this->makeNotifiable('Carlos Mena')],
            ],
            [
                'key' => 'fleetops.notification.order-dispatched',
                'package' => 'fleetops-api',
                'kind' => 'notification',
                'class' => OrderDispatched::class,
                'label' => 'OrderDispatched',
                'source' => 'notifications::email',
                'factory' => fn () => [new OrderDispatched($fleetOpsOrder, $waypoint), $this->makeNotifiable('Carlos Mena')],
            ],
            [
                'key' => 'fleetops.notification.order-dispatch-failed',
                'package' => 'fleetops-api',
                'kind' => 'notification',
                'class' => OrderDispatchFailed::class,
                'label' => 'OrderDispatchFailed',
                'source' => 'notifications::email',
                'factory' => function () use ($fleetOpsOrder) {
                    $event = new CoreOrderDispatchFailedEvent($fleetOpsOrder, 'No se encontro un conductor disponible en el radio configurado.');

                    return [new OrderDispatchFailed($fleetOpsOrder, $event), $this->makeNotifiable('Despacho KANTRACK')];
                },
            ],
            [
                'key' => 'fleetops.notification.order-failed',
                'package' => 'fleetops-api',
                'kind' => 'notification',
                'class' => OrderFailed::class,
                'label' => 'OrderFailed',
                'source' => 'notifications::email',
                'factory' => fn () => [new OrderFailed($fleetOpsOrder, 'La prueba de entrega fue rechazada por el cliente.', $waypoint), $this->makeNotifiable('Carlos Mena')],
            ],
            [
                'key' => 'fleetops.notification.order-split',
                'package' => 'fleetops-api',
                'kind' => 'notification',
                'class' => OrderSplit::class,
                'label' => 'OrderSplit',
                'source' => 'notifications::email',
                'factory' => fn () => [new OrderSplit(), $this->makeNotifiable('Equipo KANTRACK')],
            ],
            [
                'key' => 'fleetops.notification.waypoint-completed',
                'package' => 'fleetops-api',
                'kind' => 'notification',
                'class' => WaypointCompleted::class,
                'label' => 'WaypointCompleted',
                'source' => 'notifications::email',
                'factory' => fn () => [new WaypointCompleted($waypoint, $activity), $this->makeNotifiable('Carlos Mena')],
            ],
            [
                'key' => 'storefront.notification.order-accepted',
                'package' => 'storefront-api',
                'kind' => 'notification',
                'class' => StorefrontOrderAccepted::class,
                'label' => 'StorefrontOrderAccepted',
                'source' => 'notifications::email',
                'factory' => fn () => [$this->makeStorefrontNotification(StorefrontOrderAccepted::class, [
                    'order' => $storefrontOrder,
                    'storefront' => $storefront,
                    'subject' => 'Tu pedido de Central Market fue aceptado',
                    'body' => 'Tu pedido fue aceptado y ya esta siendo procesado en KANTRACK.',
                    'status' => 'order_accepted',
                ]), $this->makeNotifiable('Valeria Cliente')],
            ],
            [
                'key' => 'storefront.notification.order-canceled',
                'package' => 'storefront-api',
                'kind' => 'notification',
                'class' => StorefrontOrderCanceled::class,
                'label' => 'StorefrontOrderCanceled',
                'source' => 'notifications::email',
                'factory' => fn () => [$this->makeStorefrontNotification(StorefrontOrderCanceled::class, [
                    'order' => $storefrontOrder,
                    'storefront' => $storefront,
                    'subject' => 'Tu pedido de Central Market fue cancelado',
                    'body' => 'Tu pedido de Central Market fue cancelado. Si tu tarjeta ya fue cobrada, procesaremos el reembolso.',
                    'status' => 'order_canceled',
                ]), $this->makeNotifiable('Valeria Cliente')],
            ],
            [
                'key' => 'storefront.notification.order-completed',
                'package' => 'storefront-api',
                'kind' => 'notification',
                'class' => StorefrontOrderCompleted::class,
                'label' => 'StorefrontOrderCompleted',
                'source' => 'notifications::email',
                'factory' => fn () => [$this->makeStorefrontNotification(StorefrontOrderCompleted::class, [
                    'order' => $storefrontOrder,
                    'storefront' => $storefront,
                    'subject' => 'Tu pedido de Central Market fue entregado',
                    'body' => 'Tu pedido de Central Market fue entregado. Gracias por elegir KANTRACK.',
                    'status' => 'order_completed',
                ]), $this->makeNotifiable('Valeria Cliente')],
            ],
            [
                'key' => 'storefront.notification.order-created',
                'package' => 'storefront-api',
                'kind' => 'notification',
                'class' => StorefrontOrderCreated::class,
                'label' => 'StorefrontOrderCreated',
                'source' => 'notifications::email',
                'factory' => fn () => [$this->makeStorefrontNotification(StorefrontOrderCreated::class, [
                    'order' => $storefrontOrder,
                    'storefront' => $storefront,
                ]), $this->makeNotifiable('Tienda Central Market')],
            ],
            [
                'key' => 'storefront.notification.order-driver-assigned',
                'package' => 'storefront-api',
                'kind' => 'notification',
                'class' => StorefrontOrderDriverAssigned::class,
                'label' => 'StorefrontOrderDriverAssigned',
                'source' => 'notifications::email',
                'factory' => fn () => [$this->makeStorefrontNotification(StorefrontOrderDriverAssigned::class, [
                    'order' => $storefrontOrder,
                    'storefront' => $storefront,
                    'driver' => $storefrontOrder->driverAssigned,
                    'subject' => 'Tu conductor es Diego Soto',
                    'body' => 'Se asigno un conductor a tu pedido de Central Market. En este momento va camino al punto de recogida.',
                    'status' => 'order_driver_assigned',
                ]), $this->makeNotifiable('Valeria Cliente')],
            ],
            [
                'key' => 'storefront.notification.order-enroute',
                'package' => 'storefront-api',
                'kind' => 'notification',
                'class' => StorefrontOrderEnroute::class,
                'label' => 'StorefrontOrderEnroute',
                'source' => 'notifications::email',
                'factory' => fn () => [$this->makeStorefrontNotification(StorefrontOrderEnroute::class, [
                    'order' => $storefrontOrder,
                    'storefront' => $storefront,
                    'subject' => 'Tu pedido de Central Market va en camino',
                    'body' => 'Tu pedido de Central Market ya fue recogido. Te avisaremos cuando este cerca.',
                    'status' => 'order_enroute',
                ]), $this->makeNotifiable('Valeria Cliente')],
            ],
            [
                'key' => 'storefront.notification.order-nearby',
                'package' => 'storefront-api',
                'kind' => 'notification',
                'class' => StorefrontOrderNearby::class,
                'label' => 'StorefrontOrderNearby',
                'source' => 'notifications::email',
                'factory' => fn () => [$this->makeStorefrontNotification(StorefrontOrderNearby::class, [
                    'order' => $storefrontOrder,
                    'storefront' => $storefront,
                    'distance' => 850,
                    'time' => 420,
                    'subject' => 'Tu pedido esta cerca',
                    'body' => 'Tu pedido de Central Market llega en 7 minutos',
                    'status' => 'order_nearby',
                ]), $this->makeNotifiable('Valeria Cliente')],
            ],
            [
                'key' => 'storefront.notification.order-preparing',
                'package' => 'storefront-api',
                'kind' => 'notification',
                'class' => StorefrontOrderPreparing::class,
                'label' => 'StorefrontOrderPreparing',
                'source' => 'notifications::email',
                'factory' => fn () => [$this->makeStorefrontNotification(StorefrontOrderPreparing::class, [
                    'order' => $storefrontOrder,
                    'storefront' => $storefront,
                    'subject' => 'Tu pedido de Central Market esta en preparacion',
                    'body' => 'Tu pedido ya esta siendo preparado.',
                    'status' => 'order_preparing',
                ]), $this->makeNotifiable('Valeria Cliente')],
            ],
            [
                'key' => 'storefront.notification.order-ready-for-pickup',
                'package' => 'storefront-api',
                'kind' => 'notification',
                'class' => StorefrontOrderReadyForPickup::class,
                'label' => 'StorefrontOrderReadyForPickup',
                'source' => 'notifications::email',
                'factory' => fn () => [$this->makeStorefrontNotification(StorefrontOrderReadyForPickup::class, [
                    'order' => $storefrontOrder,
                    'storefront' => $storefront,
                    'subject' => 'Tu pedido de Central Market esta listo para retirar',
                    'body' => 'Ya puedes pasar a retirar tu pedido.',
                    'status' => 'order_ready',
                ]), $this->makeNotifiable('Valeria Cliente')],
            ],
        ];
    }

    private function buildCatalogHtml(array $results, array $summary, array $discovered): string
    {
        $cards = implode("\n", array_map(function (array $result, int $index) {
            $badgeClass = $result['status'] === 'rendered' ? 'success' : 'error';
            $subject = $result['subject'] ? htmlspecialchars($result['subject'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'Sin asunto disponible';
            $iframe = $result['html']
                ? '<iframe class="preview-frame" loading="lazy" srcdoc="' . htmlspecialchars($result['html'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"></iframe>'
                : '<div class="error-box"><strong>No se pudo renderizar.</strong><pre>' . htmlspecialchars($result['error'] ?? 'Error desconocido', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre></div>';

            return <<<HTML
<section class="card">
    <div class="meta">
        <div class="eyebrow">{$this->escapeForHtml(sprintf('%02d', $index + 1))} - {$this->escapeForHtml($result['package'])} - {$this->escapeForHtml($result['kind'])}</div>
        <h2>{$this->escapeForHtml($result['label'])}</h2>
        <p><strong>Clase:</strong> {$this->escapeForHtml($result['class'])}</p>
        <p><strong>Fuente:</strong> {$this->escapeForHtml($result['source'])}</p>
        <p><strong>Asunto:</strong> {$subject}</p>
        <span class="badge {$badgeClass}">{$this->escapeForHtml($result['status'])}</span>
    </div>
    {$iframe}
</section>
HTML;
        }, $results, array_keys($results)));

        $mailablesList = implode('', array_map(fn (string $class) => '<li>' . $this->escapeForHtml($class) . '</li>', $discovered['mailables']));
        $notificationsList = implode('', array_map(fn (string $class) => '<li>' . $this->escapeForHtml($class) . '</li>', $discovered['notifications']));
        $viewsList = implode('', array_map(fn (string $view) => '<li>' . $this->escapeForHtml($view) . '</li>', $discovered['view_files']));

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catalogo de Plantillas de Email - KANTRACK</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3efe6;
            --panel: #fffdf8;
            --ink: #17212b;
            --muted: #66727f;
            --line: #d8ccba;
            --accent: #cb5d3f;
            --success: #1f7a52;
            --success-soft: #d9efe4;
            --error: #9f2f2f;
            --error-soft: #f6d6d6;
            --shadow: 0 12px 30px rgba(23, 33, 43, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top left, rgba(203, 93, 63, 0.12), transparent 32%),
                linear-gradient(180deg, #f8f3ea 0%, var(--bg) 100%);
            color: var(--ink);
        }
        .page {
            width: min(1440px, calc(100% - 40px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }
        .hero, .inventory, .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: var(--shadow);
        }
        .hero {
            padding: 28px 30px;
            margin-bottom: 24px;
        }
        .hero h1 {
            margin: 0 0 12px;
            font-size: clamp(30px, 4vw, 52px);
            line-height: 1.02;
        }
        .hero p {
            margin: 0;
            max-width: 920px;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.6;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-top: 22px;
        }
        .stat {
            padding: 16px 18px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: #fffaf2;
        }
        .stat .label {
            display: block;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .stat .value {
            font-size: 30px;
            font-weight: 700;
        }
        .inventory {
            padding: 24px 28px;
            margin-bottom: 24px;
        }
        .inventory h2 {
            margin: 0 0 14px;
            font-size: 26px;
        }
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .inventory-grid h3 {
            margin: 0 0 8px;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--accent);
        }
        .inventory-grid ul {
            margin: 0;
            padding-left: 18px;
            color: var(--muted);
            line-height: 1.6;
            font-size: 14px;
        }
        .catalog {
            display: grid;
            gap: 22px;
        }
        .card {
            padding: 18px;
        }
        .meta {
            display: grid;
            gap: 6px;
            margin-bottom: 16px;
        }
        .meta h2 {
            margin: 0;
            font-size: 24px;
        }
        .meta p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
            word-break: break-word;
        }
        .eyebrow {
            font-size: 12px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--accent);
        }
        .badge {
            display: inline-flex;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .badge.success {
            background: var(--success-soft);
            color: var(--success);
        }
        .badge.error {
            background: var(--error-soft);
            color: var(--error);
        }
        .preview-frame {
            width: 100%;
            min-height: 760px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: #f5f7fb;
        }
        .error-box {
            border: 1px solid #e6b7b7;
            background: #fff2f2;
            color: #7a1f1f;
            border-radius: 18px;
            padding: 18px;
        }
        .error-box pre {
            margin: 10px 0 0;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: Consolas, monospace;
            font-size: 13px;
            line-height: 1.5;
        }
        @media (max-width: 720px) {
            .page { width: min(100%, calc(100% - 20px)); }
            .hero, .inventory, .card { border-radius: 18px; }
            .preview-frame { min-height: 620px; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <h1>Catalogo actual de plantillas de email de KANTRACK</h1>
            <p>Inventario generado desde las clases reales del backend que hoy pueden producir correos. Se incluyen mailables dedicados y notificaciones que renderizan la plantilla generica de Laravel. Si una tarjeta sale en error, eso tambien forma parte del estado actual del sistema.</p>
            <div class="stats">
                <div class="stat"><span class="label">Plantillas Totales</span><span class="value">{$summary['total_templates']}</span></div>
                <div class="stat"><span class="label">Mailables</span><span class="value">{$summary['mailables']}</span></div>
                <div class="stat"><span class="label">Notifications</span><span class="value">{$summary['notifications']}</span></div>
                <div class="stat"><span class="label">Renderizadas</span><span class="value">{$summary['rendered']}</span></div>
                <div class="stat"><span class="label">Con Error</span><span class="value">{$summary['failed']}</span></div>
                <div class="stat"><span class="label">Views Fisicas</span><span class="value">{$summary['dedicated_mail_view_files']}</span></div>
            </div>
        </section>

        <section class="inventory">
            <h2>Inventario Detectado</h2>
            <div class="inventory-grid">
                <div>
                    <h3>Mailables Detectados</h3>
                    <ul>{$mailablesList}</ul>
                </div>
                <div>
                    <h3>Notifications con toMail</h3>
                    <ul>{$notificationsList}</ul>
                </div>
                <div>
                    <h3>Views de Mail Encontradas</h3>
                    <ul>{$viewsList}</ul>
                </div>
            </div>
        </section>

        <section class="catalog">
            {$cards}
        </section>
    </main>
</body>
</html>
HTML;
    }

    private function discoverCurrentInventory(): array
    {
        $mailables = [];
        $notifications = [];
        $viewFiles = [];
        $scanRoots = [
            base_path('vendor/fleetbase/core-api/src'),
            base_path('vendor/fleetbase/core-api/views'),
            base_path('vendor/fleetbase/fleetops-api/server/src'),
            base_path('vendor/fleetbase/fleetops-api/server/resources/views'),
            base_path('vendor/fleetbase/storefront-api/server/src'),
            base_path('vendor/fleetbase/storefront-api/server/resources/views'),
        ];

        foreach ($scanRoots as $root) {
            if (!File::exists($root)) {
                continue;
            }

            foreach (File::allFiles($root) as $file) {
                $path = str_replace('\\', '/', $file->getPathname());
                $contents = File::get($file->getPathname());
                $class = $this->extractClassFromFile($contents);

                if ($class && Str::contains($contents, 'extends Mailable')) {
                    $mailables[] = $class;
                }

                if ($class && Str::contains($contents, 'public function toMail(')) {
                    $notifications[] = $class;
                }

                if (Str::endsWith($path, '.blade.php') && (Str::contains($path, '/views/mail/') || Str::contains($path, '/resources/views/mail/'))) {
                    $viewFiles[] = Str::replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
                }
            }
        }

        sort($mailables);
        sort($notifications);
        sort($viewFiles);

        return [
            'mailables' => $mailables,
            'notifications' => $notifications,
            'view_files' => $viewFiles,
        ];
    }

    private function extractClassFromFile(string $contents): ?string
    {
        if (!preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatches)) {
            return null;
        }

        if (!preg_match('/^class\s+([A-Za-z0-9_]+)/m', $contents, $classMatches)) {
            return null;
        }

        return trim($namespaceMatches[1]) . '\\' . trim($classMatches[1]);
    }

    private function makeCompany(): Company
    {
        $company = new Company();
        $company->setRawAttributes([
            'uuid' => '10000000-0000-0000-0000-000000000001',
            'public_id' => 'cmp_kantrack',
            'name' => 'KANTRACK Logistics',
        ], true);

        return $company;
    }

    private function makeUser(array $attributes, Company $company): User
    {
        $user = new User();
        $user->setRawAttributes($attributes, true);
        $user->setRelation('company', $company);

        return $user;
    }

    private function makeCustomerContact(Company $company, User $user): Contact
    {
        $contact = new Contact();
        $contact->setRawAttributes([
            'uuid' => '20000000-0000-0000-0000-000000000001',
            'public_id' => 'contact_demo',
            'name' => 'Valeria Cliente',
            'email' => 'valeria.cliente@kantrack.test',
            'phone' => '+593999000003',
            'type' => 'customer',
            'company_uuid' => $company->uuid,
            'user_uuid' => $user->uuid,
        ], true);
        $contact->setRelation('company', $company);
        $contact->setRelation('user', $user);

        return $contact;
    }

    private function makeVerificationCode(User $user, string $code, string $for): VerificationCode
    {
        $verificationCode = new VerificationCode();
        $verificationCode->setRawAttributes([
            'uuid' => Str::uuid()->toString(),
            'code' => $code,
            'for' => $for,
        ], true);
        $verificationCode->subject_uuid = $user->uuid;
        $verificationCode->subject_type = User::class;
        $verificationCode->setRelation('subject', $user);

        return $verificationCode;
    }

    private function makeCompanyInvite(Company $company, User $sender, User $recipient): Invite
    {
        $invite = new Invite();
        $invite->setRawAttributes([
            'uuid' => '30000000-0000-0000-0000-000000000001',
            'code' => 'INV-4829',
            'uri' => 'inv-kantrack-team',
            'recipients' => [$recipient->email],
        ], true);
        $invite->setRelation('subject', $company);
        $invite->setRelation('createdBy', $sender);

        return $invite;
    }

    private function makeNetworkInvite(Network $network, User $sender): Invite
    {
        $invite = new Invite();
        $invite->setRawAttributes([
            'uuid' => '30000000-0000-0000-0000-000000000002',
            'code' => 'NTW-9941',
            'uri' => 'join-central-market',
            'recipients' => ['partner@kantrack.test'],
        ], true);
        $invite->setRelation('subject', $network);
        $invite->setRelation('createdBy', $sender);

        return $invite;
    }

    private function makeFleetOpsOrder(): Order
    {
        $order = new Order();
        $order->setRawAttributes([
            'uuid' => '40000000-0000-0000-0000-000000000001',
            'public_id' => 'order_demo',
            'scheduled_at' => '2026-03-22 10:30:00',
        ], true);
        $order->setRelation('trackingNumber', (object) ['tracking_number' => 'KT-938401']);
        $driver = new Driver();
        $driver->setRawAttributes([
            'uuid' => '50000000-0000-0000-0000-000000000001',
            'public_id' => 'driver_demo',
            'name' => 'Carlos Mena',
        ], true);
        $order->setRelation('driverAssigned', $driver);
        $order->setRelation('company', (object) ['uuid' => '10000000-0000-0000-0000-000000000001', 'public_id' => 'cmp_kantrack']);

        return $order;
    }

    private function makeWaypoint(): Waypoint
    {
        $waypoint = new Waypoint();
        $waypoint->setRawAttributes([
            'uuid' => '60000000-0000-0000-0000-000000000001',
            'public_id' => 'waypoint_demo',
            'payload_uuid' => 'payload_demo',
        ], true);
        $waypoint->setRelation('trackingNumber', (object) ['tracking_number' => 'KT-938401']);

        return $waypoint;
    }

    private function makeStorefront(): Network
    {
        $network = new Network();
        $network->setRawAttributes([
            'uuid' => '70000000-0000-0000-0000-000000000001',
            'public_id' => 'store_demo',
            'name' => 'Central Market',
        ], true);

        return $network;
    }

    private function makeStorefrontOrder(Network $storefront): Order
    {
        $order = new Order();
        $order->setRawAttributes([
            'uuid' => '80000000-0000-0000-0000-000000000001',
            'public_id' => 'order_storefront_demo',
        ], true);

        $order->setMeta([
            'storefront_id' => $storefront->public_id,
            'is_pickup' => false,
            'subtotal' => 24.50,
            'delivery_fee' => 2.75,
            'tip' => 1.50,
            'delivery_tip' => 0.75,
            'total' => 29.50,
            'currency' => 'USD',
        ]);
        $order->setRelation('customer', (object) [
            'public_id' => 'customer_demo',
            'name' => 'Valeria Cliente',
            'email' => 'valeria.cliente@kantrack.test',
            'phone' => '+593999000003',
        ]);
        $order->setRelation('company', (object) [
            'public_id' => 'cmp_kantrack',
            'name' => 'KANTRACK Logistics',
        ]);
        $driver = new Driver();
        $driver->setRawAttributes([
            'uuid' => '50000000-0000-0000-0000-000000000002',
            'public_id' => 'driver_storefront_demo',
            'name' => 'Diego Soto',
        ], true);
        $order->setRelation('driverAssigned', $driver);
        $order->setRelation('payload', (object) [
            'entities' => collect([
                (object) ['name' => 'Sandwich Cubano'],
                (object) ['name' => 'Jugo Natural'],
            ]),
            'dropoff' => (object) [
                'address' => 'Av. Amazonas y Naciones Unidas, Quito',
            ],
        ]);

        return $order;
    }

    private function makeStorefrontNotification(string $class, array $properties): object
    {
        $notification = (new ReflectionClass($class))->newInstanceWithoutConstructor();

        foreach ($properties as $property => $value) {
            $notification->{$property} = $value;
        }

        if (!isset($notification->sentAt)) {
            $notification->sentAt = now()->toDateTimeString();
        }

        if (!isset($notification->notificationId)) {
            $notification->notificationId = uniqid('notification_');
        }

        return $notification;
    }

    private function makeNotifiable(string $name): object
    {
        return (object) [
            'uuid' => Str::uuid()->toString(),
            'public_id' => Str::slug($name, '_'),
            'name' => $name,
            'email' => Str::slug($name, '.') . '@kantrack.test',
            'phone' => '+593999123456',
        ];
    }

    private function escapeForHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
