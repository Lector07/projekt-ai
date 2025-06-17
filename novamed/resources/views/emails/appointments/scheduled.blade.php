{{-- Ten komponent jest standardowym layoutem dla maili Markdown w Laravelu --}}
<x-mail::message>
    {{-- Greeting (Powitanie) --}}
    @if (! empty($greeting))
        # {{ $greeting }}
    @else
        {{-- Domyślne powitanie, jeśli nie zdefiniowano --}}
        # Witaj!
    @endif

    {{-- Intro Lines (Linie wprowadzające) --}}
    @foreach ($introLines as $line)
        {{ $line }}
    @endforeach

    {{-- Action Button (Przycisk Akcji) --}}
    @isset($actionText)
            <?php
            // Domyślna logika koloru przycisku z Laravela, można dostosować
            $color = match ($level ?? 'primary') { // $level może nie być ustawiony, więc dodajemy default
                'success' => 'success',
                'error' => 'error',
                default => 'primary', // Możesz ustawić na 'success' lub inny kolor, jeśli chcesz
            };
            ?>
        <x-mail::button :url="$actionUrl" :color="$color">
            {{ $actionText }}
        </x-mail::button>
    @endisset

    {{-- Outro Lines (Linie zamykające - jeśli jakieś dodałeś w MailMessage) --}}
    @foreach ($outroLines as $line)
        {{ $line }}
    @endforeach

    {{-- Salutation (Pozdrowienie końcowe) --}}
    @if (! empty($salutation))
        {{ $salutation }}
    @else
        Z poważaniem,
    @endif
    <br>
    {{ config('Nova Med') }} {{-- Nazwa aplikacji z konfiguracji --}}

    {{-- Subcopy (Stopka z linkiem, jeśli jest przycisk akcji) --}}
    @isset($actionText)
        <x-slot:subcopy>
            @lang(
                "Jeśli masz problemy z kliknięciem przycisku \":actionText\", skopiuj poniższy link\n".
                'i wklej go do paska adresu swojej przeglądarki internetowej:',
                [
                    'actionText' => $actionText,
                ]
            ) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
        </x-slot:subcopy>
    @endisset
</x-mail::message>
