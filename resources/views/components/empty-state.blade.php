@props([
    'icon' => 'bi-inbox',
    'title' => 'No records found',
    'description' => 'There are currently no items to display.',
    'actionText' => null,
    'actionUrl' => null,
    'actionIcon' => 'bi-plus-lg',
])

<div {{ $attributes->merge(['class' => 'cbh-empty-state']) }}>
    <div class="cbh-empty-icon">
        <i class="bi {{ $icon }}"></i>
    </div>
    <h4 class="cbh-empty-title">{{ $title }}</h4>
    <p class="cbh-empty-desc">{{ $description }}</p>

    @if ($actionUrl && $actionText)
        <a href="{{ $actionUrl }}" class="cbh-btn cbh-btn-primary">
            @if ($actionIcon)
                <i class="bi {{ $actionIcon }}"></i>
            @endif
            {{ $actionText }}
        </a>
    @endif

    {{ $slot }}
</div>
