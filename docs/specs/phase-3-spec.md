# Copilot Spec: Phase 3 — View Layer & Output Buffering Engine

## Objective
Implement native PHP template rendering, isolated output buffering, layout inheritance, component partials, and dynamic HTML output escaping helpers.

## Target Files to Create / Modify
- `framework/View/View.php`
- `framework/View/ViewFactory.php`
- `framework/View/Engine.php`
- `framework/View/Component.php`
- `bootstrap/helpers.php` (update: `e()`, `view()`)

---

## Detailed Specifications

### 1. `framework/View/Engine.php`
- **Method:** `render(string $path, array $data = []): string`
- Uses `ob_start()` and `ob_get_clean()` within isolated try/catch block.
- Uses `extract($data, EXTR_SKIP)` before `include`.
- If an exception occurs, `ob_end_clean()` must be called before throwing to prevent output leaks.

### 2. `framework/View/View.php`
- Supports `$view->layout('layouts.app')`.
- Supports `$view->section('header') ... $view->endSection()`.
- Evaluates inner view first, passes generated `$content` and `$sections` array into the layout view.

### 3. Global Helpers (`bootstrap/helpers.php`)
- `e(mixed $value): string` — Wraps `htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- `view(string $name, array $data = [], int $status = 200, array $headers = []): Response` — Resolves `ViewFactory` from container and builds a `Response`.

---

## Copilot Validation Rules
- [ ] Dot-notation view resolution MUST convert `posts.index` to `resources/views/posts/index.php`.
- [ ] No third-party template engine (like Twig or Blade parser) is permitted. Keep core footprint under 4 files.
