<x-mail-layout>
<h2 style="font-size: 18px; font-weight: 600;">
@if($currentHour < 12)
    Buenos dias, {{ $customer->name }}!
@elseif($currentHour < 18)
    Buenas tardes, {{ $customer->name }}!
@else
    Buenas noches, {{ $customer->name }}!
@endif
</h2>

<p>Estas son tus credenciales de acceso al portal de clientes de KANTRACK:</p>
<p><strong>Correo electronico:</strong> {{ $customer->user->email }}</p>
<p><strong>Contrasena temporal:</strong> {{ $plaintextPassword }}</p>
@if($customerPortalUrl)
<p><strong>URL del portal de clientes:</strong> {{ $customerPortalUrl }}</p>
@endif
<p style="font-size: 13px; color: #64748b;">Te recomendamos actualizar tu contrasena despues del primer inicio de sesion.</p>
</x-mail-layout>
