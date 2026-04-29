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

<p>Estas son tus credenciales de acceso a KANTRACK:</p>
<p><strong>Correo electronico:</strong> {{ $user->email }}</p>
<p><strong>Contrasena temporal:</strong> {{ $plaintextPassword }}</p>
<p><strong>URL de acceso:</strong> {{ \Fleetbase\Support\Utils::consoleUrl() }}</p>
<p style="font-size: 13px; color: #64748b;">Por seguridad, te recomendamos cambiar tu contrasena despues de iniciar sesion.</p>
</x-mail-layout>
