@php($brandName = 'KANTRACK')

<x-mail-layout>
<h2 style="font-size: 18px; font-weight: 600;">
@if($currentHour < 12)
    Buenos dias, {{ \Fleetbase\Support\Utils::delinkify($user->name) }}!
@elseif($currentHour < 18)
    Buenas tardes, {{ \Fleetbase\Support\Utils::delinkify($user->name) }}!
@else
    Buenas noches, {{ \Fleetbase\Support\Utils::delinkify($user->name) }}!
@endif
</h2>

@if($content)
{!! $content !!}
@else
<p>Bienvenido a {{ $brandName }}. Usa el siguiente codigo para verificar tu correo electronico y completar tu registro.</p>
<p>Tu codigo de verificacion: <code>{{ $code }}</code></p>
@endif

@if($type === 'email_verification')
    @component('mail::button', ['url' => \Fleetbase\Support\Utils::consoleUrl('onboard', ['step' => 'verify-email', 'session' => base64_encode($user->uuid), 'code' => $code ])])
        Verificar correo
    @endcomponent

    <p style="font-size: 13px; color: #64748b;">Si no solicitaste este registro, puedes ignorar este correo.</p>
@endif

</x-mail-layout>
