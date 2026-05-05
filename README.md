# Fly Framework

**Beautiful, Modern & Opinionated**

An internal-first PHP framework that produces its own technology. Not a library collection — a runtime platform.

## Philosophy

- **Beautiful** — APIs that feel natural and require no documentation to understand.
- **Modern** — Built for PHP 8.2+ with strict types, enums, readonly properties, and attributes.
- **Opinionated** — Clear architectural decisions. Convention over configuration.
- **Internal-First** — Router, container, ORM, template engine — all built from scratch.

## Quick Start

```php
// routes/web.php
Route::get('/', function () {
    return 'Fly Framework';
});
```

```bash
composer install
composer serve
```

## Core Principle

> "Simple things should feel simple. Complex things should remain possible."

## Requirements

- PHP 8.2+
- Composer (autoload only)

## License

MIT
