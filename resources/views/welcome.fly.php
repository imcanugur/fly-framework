@extends('layout')

@section('title', 'Welcome to Fly')

@section('content')
    <div @class(['welcome-container', 'is-cool' => $isCool])>
        <h2>{{ $message }}</h2>
        
        <fly:alert type="success" class="mb-4">
            <f:slot name="title">Fly Native Components!</f:slot>
            This is a component using Fly's new powerful component engine with attributes!
        </fly:alert>

        <ul>
        @foreach($features as $feature)
            <li>
                {{ $loop->iteration }}. {{ $feature }}
                @if($loop->first) <span class="badge">New!</span> @endif
            </li>
        @endforeach
        </ul>

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
