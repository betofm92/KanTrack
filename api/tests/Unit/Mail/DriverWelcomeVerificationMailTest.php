<?php

namespace Tests\Unit\Mail;

use Fleetbase\Mail\VerificationMail;
use Fleetbase\Models\User;
use Fleetbase\Models\VerificationCode;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class DriverWelcomeVerificationMailTest extends TestCase
{
    public function test_driver_welcome_verification_mail_can_be_sent_and_exported_to_html(): void
    {
        config()->set('app.name', 'KANTRACK');
        config()->set('fleetbase.console.host', 'kantrack.test');
        config()->set('fleetbase.console.secure', false);
        config()->set('fleetbase.console.subdomain', null);

        $driver = new User([
            'uuid'  => '4c5f8e9a-24e0-4bf3-8c7d-6ad6b6a8b1f2',
            'name'  => 'Carlos Mena',
            'email' => 'carlos.mena+driver@kantrack.test',
            'type'  => 'driver',
        ]);

        $verificationCode = new VerificationCode([
            'code' => '482913',
            'for'  => 'email_verification',
        ]);
        $verificationCode->subject_uuid = $driver->uuid;
        $verificationCode->subject_type = User::class;
        $verificationCode->setRelation('subject', $driver);

        $mail = new VerificationMail($verificationCode);

        Mail::fake();
        Mail::to($driver->email)->send($mail);

        Mail::assertSent(VerificationMail::class, 1);

        $renderedHtml = $mail->render();
        $standaloneHtml = $this->makeStandalonePreview($renderedHtml, $mail, $driver);
        $previewPath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'driver-welcome-email-preview.html';

        file_put_contents($previewPath, $standaloneHtml);

        $this->assertStringContainsString('Carlos Mena', $renderedHtml);
        $this->assertStringContainsString('482913', $renderedHtml);
        $this->assertStringContainsString('verify-email', $renderedHtml);
        $this->assertFileExists($previewPath);
    }

    private function makeStandalonePreview(string $renderedHtml, VerificationMail $mail, User $driver): string
    {
        if (Str::contains(Str::lower($renderedHtml), '<html')) {
            return $renderedHtml;
        }

        $subject = htmlspecialchars($mail->envelope()->subject, ENT_QUOTES, 'UTF-8');
        $recipient = htmlspecialchars($driver->email, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$subject}</title>
    <style>
        body { margin: 0; padding: 32px 20px; background: #0f172a; color: #e2e8f0; font-family: Arial, Helvetica, sans-serif; }
        .preview { max-width: 760px; margin: 0 auto; }
        .meta { margin-bottom: 18px; padding: 16px 18px; border-radius: 10px; background: #111827; border: 1px solid #334155; }
        .meta p { margin: 0 0 6px; font-size: 13px; line-height: 1.5; }
        .meta p:last-child { margin-bottom: 0; }
        .shell { background: #f1f5f9; border-radius: 12px; padding: 24px 14px; }
    </style>
</head>
<body>
    <div class="preview">
        <div class="meta">
            <p><strong>Evento simulado:</strong> Bienvenida con codigo de verificacion para conductor recien creado</p>
            <p><strong>Para:</strong> {$recipient}</p>
            <p><strong>Asunto:</strong> {$subject}</p>
            <p><strong>Mailable:</strong> Fleetbase\\Mail\\VerificationMail</p>
        </div>
        <div class="shell">
{$renderedHtml}
        </div>
    </div>
</body>
</html>
HTML;
    }

}
