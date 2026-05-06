<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Fly Framework')</title>
</head>
<body>
    <header>
        <h1>Fly Framework</h1>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        <p>&copy; 2026 Fly Framework</p>
    </footer>

    @stack('scripts')
</body>
</html>
