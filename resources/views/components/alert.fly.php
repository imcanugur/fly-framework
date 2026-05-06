@props(['type' => 'info', 'title' => null])

@css
<style>
    .fly-alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #ddd; }
    .fly-alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
    .fly-alert-info { background: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
</style>
@endcss

<div {!! $attributes->merge(['class' => 'fly-alert fly-alert-' . $type]) !!}>
    @isset($title)
        <h4 class="alert-title">{{ $title }}</h4>
    @endisset
    
    <div class="alert-content">
        {{ $slot }}
    </div>
</div>

@js
<script>
    console.log('Fly Alert Component Initialized!');
</script>
@endjs
