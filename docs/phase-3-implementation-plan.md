# Phase 3: View Layer & Output Buffering Engine

**Duration:** Week 4

---

## 1. What to Build

Phase 3 introduces the view rendering system, output buffering engine, template inheritance (layouts and sections), and dynamic component rendering without dynamic engine evaluation overhead or external dependencies.

### Core Deliverables:

- **`framework/View/View.php`** — Lightweight view object holding path, data array, layout context, and rendering logic.
- **`framework/View/ViewFactory.php`** — Factory service registering view paths (`resources/views/`) and constructing view instances.
- **`framework/View/Engine.php`** — Native PHP rendering engine leveraging isolated `ob_start()` / `ob_get_clean()` buffers.
- **`framework/View/Component.php`** — Reusable layout component contract for server-side HTML rendering.
- **`bootstrap/helpers.php` updates** — Global helper function `view($view, $data, $status, $headers)` and HTML output escaping function `e($string)`.

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Controller Helper Integration:** Controllers defined in Phase 2 can call `$this->view()` or global helper `view()` to produce HTML responses directly compatible with Phase 1's `Response`.
- **Global Helper Functions:** Extends `bootstrap/helpers.php` initialized in Phase 0.
- **Container Registration:** `ViewFactory` is registered as a singleton inside Phase 0's `Container`.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **Global Escaping Helper (`bootstrap/helpers.php` additions)**
   ```php
   if (!function_exists('e')) {
       function e(mixed $value): string {
           return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
       }
   }

   if (!function_exists('view')) {
       function view(string $name, array $data = [], int $status = 200, array $headers = []): \Nexus\Http\Response {
           $factory = \Nexus\Foundation\Application::getInstance()->make(\Nexus\View\ViewFactory::class);
           $html = $factory->make($name, $data)->render();
           return new \Nexus\Http\Response($html, $status, $headers);
       }
   }
   ```

2. **`framework/View/Engine.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\View;

   class Engine
   {
       public function render(string $path, array $data = []): string
       {
           if (!file_exists($path)) {
               throw new \InvalidArgumentException("View file not found: [$path]");
           }

           extract($data, EXTR_SKIP);

           ob_start();
           try {
               include $path;
           } catch (\Throwable $e) {
               ob_end_clean();
               throw $e;
           }

           return ob_get_clean();
       }
   }
   ```

3. **`framework/View/View.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\View;

   class View
   {
       protected ?string $layout = null;
       protected array $sections = [];
       protected ?string $currentSection = null;

       public function __construct(
           protected Engine $engine,
           protected string $path,
           protected array $data = []
       ) {}

       public function layout(string $layoutName): static
       {
           $this->layout = $layoutName;
           return $this;
       }

       public function section(string $name): void
       {
           $this->currentSection = $name;
           ob_start();
       }

       public function endSection(): void
       {
           if ($this->currentSection === null) {
               throw new \LogicException("Cannot end a section without starting one.");
           }
           $this->sections[$this->currentSection] = ob_get_clean();
           $this->currentSection = null;
       }

       public function render(): string
       {
           $content = $this->engine->render($this->path, array_merge($this->data, ['view' => $this]));

           if ($this->layout !== null) {
               $layoutPath = dirname($this->path, 2) . '/layouts/' . str_replace('.', '/', $this->layout) . '.php';
               $layoutData = array_merge($this->data, [
                   'content' => $content,
                   'sections' => $this->sections,
                   'view' => $this
               ]);
               return $this->engine->render($layoutPath, $layoutData);
           }

           return $content;
       }
   }
   ```

4. **`framework/View/ViewFactory.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\View;

   class ViewFactory
   {
       protected Engine $engine;
       protected string $basePath;

       public function __construct(string $basePath)
       {
           $this->engine = new Engine();
           $this->basePath = rtrim($basePath, '/\\');
       }

       public function make(string $name, array $data = []): View
       {
           $relativePath = str_replace('.', '/', $name) . '.php';
           $fullPath = $this->basePath . '/' . $relativePath;

           return new View($this->engine, $fullPath, $data);
       }
   }
   ```

---

## 4. Success Criteria

- [ ] Views resolve dot-notation paths (`posts.index` → `resources/views/posts/index.php`).
- [ ] Output buffering captures rendered PHP HTML safely without output leaks on exception.
- [ ] Layout inheritance and dynamic sections work seamlessly across views.
- [ ] Context-aware output escaping helper `e()` protects against XSS vulnerabilities.
- [ ] `view()` helper returns a valid Phase 1 HTTP Response ready for pipeline flushing.
