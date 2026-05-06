<div {!! $attributes->merge(['class' => 'alert alert-' . ($type ?? 'info')]) !!}>
    @isset($title)
        <h4 class="alert-title">{{ $title }}</h4>
    @endisset
    
    <div class="alert-content">
        {{ $slot }}
    </div>
</div>
