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

<p>Este es un correo de prueba de KANTRACK para confirmar que tu configuracion de correo funciona correctamente.</p>
<table>
    <tbody>
        <tr>
            <td><strong>MAILER:</strong></td>
            <td>{{ strtoupper($mailer) }}</td>
        </tr>
        <tr>
            <td><strong>ENTORNO:</strong></td>
            <td>{{ strtoupper(app()->environment()) }}</td>
        </tr>
    </tbody>
</table>
</x-mail-layout>
