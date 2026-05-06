@props(['type' => 'info', 'title' => null])

<div {!! $attributes->merge(['class' => 'alert alert-' . $type]) !!}>
    @isset($title)
        <h4 class="alert-title">{{ $title }}</h4>
    @endisset
    
    <div class="alert-content">
        {{ $slot }}
    </div>
</div>
