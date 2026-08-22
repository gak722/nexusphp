# 17. Architecture Pattern: Server-Side Rendering (SSR)

Server-Side Rendering (SSR) delivers HTML directly from the server for maximum SEO performance, zero client JS execution wait times, and robust session-based security.

---

## 1. Core Pattern Requirements

- **Authentication:** Session-based authentication (`Nexus\Security\Auth`).
- **Protection:** Automatic CSRF form validation (`Nexus\Security\Csrf`).
- **Templates:** Isolated output buffering view factory (`Nexus\View\Engine`).

---

## 2. Controller Action Implementation

```php
namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Security\Auth;
use Nexus\Security\Csrf;
use App\Models\Post;

class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = Post::orderBy('created_at', 'DESC')->get();

        return $this->view('blog.index', [
            'posts' => $posts,
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request): Response
    {
        // CSRF Token validation
        if (!Csrf::validate($request->input('_token'))) {
            return $this->response('403 Invalid CSRF Token', 403);
        }

        $post = new Post();
        $post->title = $request->input('title');
        $post->content = $request->input('content');
        $post->user_id = Auth::user()->id;
        $post->save();

        return $this->redirect('/blog');
    }
}
```

---

## 3. Native View Template (`resources/views/blog/index.php`)

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Latest Articles</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header>
        <h1>Developer Blog</h1>
        <?php if ($user): ?>
            <p>Welcome, <?= e($user->name) ?> | <a href="/logout">Logout</a></p>
        <?php else: ?>
            <a href="/login">Login</a>
        <?php endif; ?>
    </header>

    <main>
        <?php foreach ($posts as $post): ?>
            <article>
                <h2><?= e($post->title) ?></h2>
                <p><?= e($post->content) ?></p>
            </article>
        <?php endforeach; ?>
    </main>
</body>
</html>
```

---

## 4. Next Steps

Explore modern frontend bundling with Vite in [18. Architecture Pattern: TALL Stack / Vite Integration](18-arch-tall-vite.md).
