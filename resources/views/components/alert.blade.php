@props([
    'type' => 'success',
    'dismissible' => true,
    'icon' => null,
])

@php
    $defaultIcons = [
        'success' => 'bi-check-circle-fill',
        'danger' => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info' => 'bi-info-circle-fill',
    ];
    $iconClass = $icon ?? ($defaultIcons[$type] ?? 'bi-info-circle-fill');
@endphp

<div {{ $attributes->merge(['class' => 'cbh-alert cbh-alert-' . $type]) }} role="alert">
    <i class="bi {{ $iconClass }} cbh-alert-icon"></i>
    <div class="flex-grow-1">
        {{ $slot }}
    </div>
    @if ($dismissible)
        <button type="button" class="cbh-alert-close" onclick="this.closest('.cbh-alert').remove()" aria-label="Dismiss alert">
            <i class="bi bi-x-lg" style="font-size: 0.75rem;"></i>
        </button>
    @endif
</div>
