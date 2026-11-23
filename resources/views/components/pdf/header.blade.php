@props([
    'title',
    'subtitle' => null,
])

@php
    $geradoPor = auth()->user()?->name;
@endphp

<div class="pdf-header">
    <h1 class="pdf-header__title">{{ $title }}</h1>
    @if($subtitle)
        <div class="pdf-header__subtitle">{{ $subtitle }}</div>
    @endif
    <div class="pdf-header__stamp">
        Gerado em {{ now()->format('d/m/Y \à\s H:i') }}@if($geradoPor) · por {{ $geradoPor }}@endif
    </div>
</div>

@if(filled(trim($slot)))
    <div class="pdf-header__context">{{ $slot }}</div>
@endif
