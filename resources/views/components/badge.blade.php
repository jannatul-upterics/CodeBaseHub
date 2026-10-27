@props([
    'variant' => 'neutral',
    'dot' => false,
])

<span {{ $attributes->merge(['class' => 'cbh-badge cbh-badge-' . $variant]) }}>
    @if ($dot)
        <i class="bi bi-circle-fill" style="font-size: 0.38rem;"></i>
    @endif
    {{ $slot }}
</span>
