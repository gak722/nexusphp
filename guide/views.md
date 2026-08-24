# Views

This guide provides a comprehensive overview of how the view system works in the NexusPHP framework, based entirely on its actual implementation.

## Overview

Views are responsible for separating your application's presentation logic from its business and controller logic. In NexusPHP, the view system is built around native PHP templates. Rather than introducing a heavy, proprietary templating language (like Blade or Twig), NexusPHP utilizes an isolated output-buffering engine (`Nexus\View\Engine`) that runs pure PHP `.`php files. This ensures maximum performance while keeping the templating system lightweight and completely dependency-free.

## What are Views?

A view is simply a PHP file that contains HTML mixed with PHP control structures (like `foreach`, `if`, etc.). When a controller wants to return a webpage, it passes an array of data to a view. The framework extracts this data into variables, executes the PHP file within an isolated buffer, and returns the resulting HTML string as an HTTP Response.

## Creating Views

### View File Locations

By default, NexusPHP looks for view files in the `resources/views` directory of your application root. 

You can organize your views into subdirectories. When referencing a view, NexusPHP uses "dot notation" to represent directory separators. 

For example:
- `view('home')` resolves to `resources/views/home.php`
- `view('users.profile')` resolves to `resources/views/users/profile.php`

### View File Format

NexusPHP uses native `.php` files. Inside these files, you have access to any variables passed to the view, as well as the special `$view` object which provides layout and asset helpers.

### Basic View Example

```php
<!-- resources/views/users/profile.php -->
<div class="user-profile">
    <!-- Use the global e() helper to prevent XSS -->
    <h1>Welcome, <?= e($user->name) ?></h1>
    <p>Email: <?= e($user->email) ?></p>
</div>
```

## Rendering Views from Controllers

To render a view and return it as an HTTP Response, you can use the global `view()` helper function or the `$this->view()` method provided by the base `Nexus\Http\Controller`.

### Passing Data to Views

Data is passed to views as an associative array. The view engine uses PHP's `extract()` function, which turns the array keys into usable variables inside the template.

```php
namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\Response;

class ProfileController extends Controller
{
    public function show(int $id): Response
    {
        $user = $this->repository->find($id);

        // Using the Controller helper
        return $this->view('users.profile', ['user' => $user]);
        
        // OR using the global helper
        // return view('users.profile', ['user' => $user]);
    }
}
```

## View Features

NexusPHP's `Nexus\View\View` object provides robust support for Layouts and Sections to help you keep your HTML DRY (Don't Repeat Yourself).

### Layouts & Sections

A layout is a "master" view template that defines the overall structure of your page (like the `<html>`, `<head>`, and `<body>` tags). Child views can declare that they use a specific layout, and then define "sections" to inject content into that layout.

**1. Defining the Layout:**
Use the `$view->yield('section_name')` method inside your layout to designate where child content should be placed. 

```php
<!-- resources/views/layouts/app.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $view->yield('title', 'NexusPHP Application') ?></title>
    <!-- Asset cache-busting helper -->
    <link rel="stylesheet" href="<?= $view->asset('css/style.css') ?>">
</head>
<body>
    <header>
        <nav>...</nav>
    </header>

    <main>
        <!-- The main child content is automatically passed as $content -->
        <?= $content ?? '' ?>
    </main>

    <footer>
        <?= $view->yield('footer') ?>
    </footer>
</body>
</html>
```

**2. Extending the Layout in a Child View:**
Inside your child view, call `$view->layout('name')` to specify the layout. Use `$view->section('name')` and `$view->endSection()` to define content blocks. Anything outside a defined section is passed to the layout as the `$content` variable.

```php
<!-- resources/views/home.php -->
<?php $view->layout('layouts.app'); ?>

<?php $view->section('title'); ?>
Home Page
<?php $view->endSection(); ?>

<div class="welcome">
    <h1>Welcome to NexusPHP</h1>
    <p>This will be injected into the $content variable of the layout.</p>
</div>

<?php $view->section('footer'); ?>
    <p>Custom footer for the home page.</p>
<?php $view->endSection(); ?>
```

### Partial Views (Includes)

To include a partial view (like a reusable navigation bar or sidebar) inside another view, you can simply use standard PHP `include` pointing to the resolved path, or utilize reusable view components.

*(Note: Currently, the View Engine natively focuses on layout resolution; direct partial rendering via a dedicated `$view->include()` method is not built into the base `View` object, so standard PHP `include` or instantiating a View/Component is recommended.)*

### View Components

NexusPHP provides a `Nexus\View\Component` abstract class for building reusable UI elements with encapsulated logic.

You can create a class extending `Component`, implement the `render()` method to return an HTML string, and echo it directly in your views (it implements `__toString()`).

```php
class AlertComponent extends \Nexus\View\Component
{
    public function __construct(protected string $message) {}
    
    public function render(): string
    {
        return '<div class="alert">' . e($this->message) . '</div>';
    }
}
```
Inside the view:
```php
<?= new AlertComponent('Operation successful!') ?>
```

[TODO: Verify if there is a centralized view component factory or directive system, as the base framework currently provides the abstract contract.]

## View Helpers

### Asset Helper

The `$view` object provides an `asset()` helper method. This method takes a relative path (e.g., `css/app.css`), locates the file in your `public/` directory, and automatically appends a query string cache-buster based on the file's MD5 hash (e.g., `/css/app.css?v=a1b2c3d4`).

```php
<script src="<?= $view->asset('js/app.js') ?>"></script>
```

### Security (Escaping Output)

Because NexusPHP uses native PHP templates, you must manually escape any user-provided data before echoing it to prevent Cross-Site Scripting (XSS) attacks.

NexusPHP provides a global `e()` helper function (defined in `bootstrap/helpers.php`) which is a wrapper around `htmlspecialchars()`.

```php
<!-- DANGEROUS: Do not do this with user input -->
<?= $user->bio ?>

<!-- SAFE: Always escape user input -->
<?= e($user->bio) ?>
```

The `e()` function automatically uses `ENT_QUOTES | ENT_SUBSTITUTE` and `UTF-8` encoding.

## Organization Best Practices

1. **Use Layouts Heavily**: Define one or two master layouts (`layouts.app`, `layouts.admin`) and have all your page views extend them using `$view->layout()`. This keeps your HTML headers and structural wrappers in one place.
2. **Always Escape**: Get into the habit of wrapping every output variable in `e()`. Only omit it if you are absolutely sure the variable contains safe, pre-rendered HTML.
3. **Keep Views Dumb**: Avoid doing database queries or complex logic inside your view templates. Pass all necessary data from the controller via the data array.
4. **Use Dot Notation**: Always use dot notation (`folder.view`) rather than slashes (`folder/view.php`) when referencing views for consistency and safety against path traversal.

## Next Steps

- [Controllers](controllers.md): Learn how to fetch data and return views.
- [Requests & Responses](requests-responses.md): Understand the HTTP lifecycle.
- [Database Connections](database.md): Learn how to retrieve data to pass to your views.
