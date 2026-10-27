@props([
    'label',
    'value',
    'icon' => 'bi-layers',
    'color' => 'blue',
    'footerText' => null,
    'footerHref' => null,
])

<div {{ $attributes->merge(['class' => 'cbh-stat-card']) }}>
    <div class="cbh-stat-header">
        <div>
            <div class="cbh-stat-label">{{ $label }}</div>
            <div class="cbh-stat-value">{{ $value }}</div>
        </div>
        <div class="cbh-stat-icon cbh-stat-icon-{{ $color }}">
            <i class="bi {{ $icon }}"></i>
        </div>
    </div>

    @if ($footerHref)
        <div class="cbh-stat-footer">
            <a href="{{ $footerHref }}" class="fw-semibold" style="color: var(--cbh-brand-600); font-size: 0.78rem;">
                {{ $footerText ?? 'View all →' }}
            </a>
        </div>
    @elseif ($footerText)
        <div class="cbh-stat-footer">
            {{ $footerText }}
        </div>
    @endif
</div>
