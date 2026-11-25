<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? ($page->meta_title ?: $page->title) }}</title>
    @if (! empty($metaDescription ?? $page->meta_description))
        <meta name="description" content="{{ $metaDescription ?? $page->meta_description }}">
    @endif
    @if (! empty($page->canonical_url))
        <link rel="canonical" href="{{ $page->canonical_url }}">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/cbh-design-system.css') }}">
</head>
<body class="bg-light text-dark">
    <main class="container py-5">
        <header class="mb-4">
            <h1 class="display-5 fw-bold">{{ $page->title }}</h1>
            @if (! empty($page->content))
                <p class="lead text-secondary mt-3">{{ $page->content }}</p>
            @endif
        </header>

        @php
            $sectionsToRender = $pageSections ?? ($page->sections ?? []);
        @endphp

        @foreach ($sectionsToRender as $section)
            @php
                $sectionType = is_array($section) ? ($section['section_type'] ?? 'content') : ($section->section_type ?? 'content');
                $content = is_array($section) ? ($section['content'] ?? []) : ($section->content ?? []);
                if (is_string($content)) {
                    $decoded = json_decode($content, true);
                    $content = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : ['body' => $content];
                }
            @endphp
            <section class="card border-0 shadow-sm mb-4" data-section-type="{{ $sectionType }}">
                <div class="card-body p-4">
                    @if (! empty($content['heading']))
                        <h2 class="h4 fw-semibold mb-2">{{ $content['heading'] }}</h2>
                    @endif
                    @if (! empty($content['subheading']))
                        <p class="text-muted mb-3">{{ $content['subheading'] }}</p>
                    @endif
                    @if (! empty($content['text']))
                        <p class="mb-3">{{ $content['text'] }}</p>
                    @endif
                    @if (! empty($content['body']))
                        <p class="mb-3">{{ $content['body'] }}</p>
                    @endif
                    @if (! empty($content['items']) && is_array($content['items']))
                        <ul class="list-group list-group-flush mb-3">
                            @foreach ($content['items'] as $item)
                                <li class="list-group-item px-0">
                                    @if (is_array($item))
                                        <strong>{{ $item['title'] ?? '' }}</strong>
                                        @if (! empty($item['body'] ?? $item['description'] ?? null))
                                            <span class="text-muted"> — {{ $item['body'] ?? $item['description'] }}</span>
                                        @endif
                                    @else
                                        {{ $item }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($content['button_text']))
                        <a href="{{ $content['button_url'] ?? '#' }}" class="btn btn-primary">
                            {{ $content['button_text'] }}
                        </a>
                    @endif
                </div>
            </section>
        @endforeach
    </main>
</body>
</html>
