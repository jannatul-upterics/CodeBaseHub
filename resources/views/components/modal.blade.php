@props([
    'id',
    'title',
    'size' => 'md',
])

@php
    $dialogClass = 'modal-dialog modal-dialog-centered';
    if ($size === 'lg') {
        $dialogClass .= ' modal-lg';
    } elseif ($size === 'sm') {
        $dialogClass .= ' modal-sm';
    }
@endphp

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="{{ $dialogClass }}">
        <div class="modal-content cbh-card" style="border: 1px solid var(--cbh-border); box-shadow: var(--cbh-shadow-xl);">
            <div class="cbh-card-header">
                <h5 class="cbh-card-title" id="{{ $id }}Label">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
            </div>
            <div class="cbh-card-body">
                {{ $slot }}
            </div>
            @if (isset($footer))
                <div class="cbh-card-footer d-flex justify-content-end gap-2">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
