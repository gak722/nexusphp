# 18. Architecture Pattern: TALL Stack / Vite Integration

Integrate modern frontend build tools (Vite, TailwindCSS, Alpine.js) seamlessly into your NexusPHP application.

---

## 1. Vite Manifest Helper

To load compiled Vite assets in development and production without node process dependencies at runtime, use a simple Manifest Resolver helper:

```php
namespace App\Support;

class Vite
{
    public static function assets(string $entry): string
    {
        $manifestPath = app()->basePath('public/build/manifest.json');

        // Development mode: load directly from Vite dev server
        if (env('APP_ENV') === 'development' && file_exists(app()->basePath('public/hot'))) {
            $devUrl = file_get_contents(app()->basePath('public/hot'));
            return "<script type=\"module\" src=\"{$devUrl}/{$entry}\"></script>";
        }

        // Production mode: resolve compiled bundle from manifest.json
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest[$entry])) {
                $file = $manifest[$entry]['file'];
                $css = $manifest[$entry]['css'][0] ?? null;
                $html = '';
                if ($css) {
                    $html .= "<link rel=\"stylesheet\" href=\"/build/{$css}\">\n";
                }
                $html .= "<script type=\"module\" src=\"/build/{$file}\"></script>";
                return $html;
            }
        }

        return "<!-- Vite asset manifest not found -->";
    }
}
```

---

## 2. Incorporating Vite Assets into Layouts

In your view template:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NexusPHP + Vite Application</title>
    <?= \App\Support\Vite::assets('resources/js/app.js') ?>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">
    <div class="p-8 bg-gray-800 rounded-xl shadow-2xl">
        <h1 class="text-3xl font-bold text-indigo-400">TailwindCSS + Alpine.js Active</h1>
        <div x-data="{ count: 0 }" class="mt-4">
            <button @click="count++" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-white">
                Increment Counter: <span x-text="count"></span>
            </button>
        </div>
    </div>
</body>
</html>
```

---

## 3. Next Steps

Explore asynchronous event-driven microservices in [19. Architecture Pattern: Event-Driven Microservices](19-arch-event-microservices.md).
