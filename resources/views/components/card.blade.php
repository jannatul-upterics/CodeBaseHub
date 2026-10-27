@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'cbh-card']) }}>
    @if ($title || isset($header) || isset($headerAction))
        <div class="cbh-card-header">
            @if (isset($header))
                {{ $header }}
            @else
                <div>
                    @if ($title)
                        <h3 class="cbh-card-title">{{ $title }}</h3>
                    @endif
                    @if ($subtitle)
                        <p class="cbh-card-subtitle">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            @if (isset($headerAction))
                <div class="cbh-card-actions">
                    {{ $headerAction }}
                </div>
            @endif
        </div>
    @endif

    <div class="cbh-card-body">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="cbh-card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
