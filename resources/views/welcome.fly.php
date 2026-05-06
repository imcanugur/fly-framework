@extends('layout')

@section('title', 'Welcome to Fly')

@section('content')
    <div @class(['welcome-container', 'is-cool' => $isCool])>
        <h2>@upper($message)</h2>
        <p class="composer-info">{{ $composer_message }}</p>
        
        <fly:alert type="success" class="mb-4">
            <f:slot name="title">Fly Native Components!</f:slot>
            This is a component using Fly's new powerful component engine with attributes and props!
        </fly:alert>

        @once('welcome-scripts')
            @push('scripts')
                <script>console.log('This script only appears ONCE!');</script>
            @endpush
        @endonce

        <ul>
        @foreach($features as $feature)
            <li>
                {{ $loop->iteration }}. {{ $feature }}
                @if($loop->first) <span class="badge">New!</span> @endif
            </li>
        @endforeach
        </ul>

        <div class="form-demo">
            <label><input type="checkbox" @checked(true)> Already checked</label>
            <label><input type="checkbox" @checked(false)> Not checked</label>
        </div>

        @if($isCool)
            <p>This is really cool!</p>
        @else
            <p>This is ok.</p>
        @endif

        @fly
            $flyVar = 'Written in raw PHP via directive!';
            echo "<p><strong>{$flyVar}</strong></p>";
        @endfly
        
        <form method="POST" action="/submit">
            @csrf
            @method('PUT')
            <button type="submit">Submit</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>console.log('Fly Engine Loaded!');</script>
@endpush
