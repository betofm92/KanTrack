@php
    $brandName = 'KANTRACK';
    $logoPath = null;
    $preferredLogoPaths = [
        'C:\\Users\\betof\\Escritorio\\KanTrack\\KanTrack\\console\\public\\images\\kantrack-logo-png.png',
        base_path('../console/public/images/kantrack-logo-png.png'),
        '/fleetbase/console/public/images/kantrack-logo-png.png',
    ];

    foreach ($preferredLogoPaths as $candidatePath) {
        if (is_string($candidatePath) && is_file($candidatePath)) {
            $logoPath = $candidatePath;
            break;
        }
    }

    $logoSrc = $logoPath
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : \Fleetbase\Support\Utils::consoleUrl('images/kantrack-logo-png.png');
@endphp

<x-mail::layout>
    <x-slot:header>
        <tr>
            <td class="header" style="background-color: #f8fafc; padding: 22px 24px;">
                <a href="{{ \Fleetbase\Support\Utils::consoleUrl() }}" style="display: inline-block;">
                    <span style="display: inline-block; background-color: #000000; padding: 12px 18px; border-radius: 10px;">
                        <img src="{{ $logoSrc }}" alt="{{ $brandName }} Logo" class="logo" style="display: block; height: 44px; width: auto; max-height: 44px; max-width: 220px;">
                    </span>
                </a>
            </td>
        </tr>
    </x-slot:header>

    @if (! empty($greeting))
# {{ $greeting }}
    @else
        @if ($level === 'error')
# Ocurrio un problema
        @else
# Hola
        @endif
    @endif

    @foreach ($introLines as $line)
{{ $line }}

    @endforeach

    @isset($actionText)
        @php
            $color = match ($level) {
                'success', 'error' => $level,
                default => 'primary',
            };
        @endphp
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
    @endisset

    @foreach ($outroLines as $line)
{{ $line }}

    @endforeach

    @if (! empty($salutation))
{{ $salutation }}
    @else
Saludos,<br>
Equipo KANTRACK
    @endif

    @isset($actionText)
        <x-slot:subcopy>
            <x-mail::subcopy>
Si tienes problemas para hacer clic en el boton "{{ $actionText }}", copia y pega la siguiente URL en tu navegador:
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    <x-slot:footer>
        <x-mail::footer>
            {{ date('Y') }} {{ $brandName }}. Todos los derechos reservados.
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
