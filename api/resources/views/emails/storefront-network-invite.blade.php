<x-mail-layout>
<h2 style="font-size: 18px; font-weight: 600;">Hola!</h2>

<p>{{ $sender->name }} te invito a unirte a la red <strong>{{ $network->name }}</strong> en KANTRACK.</p>
<p>Usa el siguiente codigo de invitacion para completar tu acceso:</p>
<p><code>{{ $invite->code }}</code></p>

@component('mail::button', ['url' => $url])
    Aceptar invitacion
@endcomponent

<p style="font-size: 13px; color: #64748b;">Si no esperabas esta invitacion, puedes ignorar este correo sin realizar ninguna accion.</p>
</x-mail-layout>
