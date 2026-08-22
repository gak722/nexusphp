<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($currentTitle ?? 'NexusPHP Framework') ?> - NexusPHP Official Documentation</title>
    <meta name="description" content="Official documentation for NexusPHP - zero-dependency, ultra-high performance PHP 8.4 framework.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: #1e293b;
            --bg-sidebar: #0b1329;
            --bg-code: #090d16;
            --border-color: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-primary: #6366f1;
            --accent-hover: #4f46e5;
            --accent-cyan: #06b6d4;
            --accent-green: #10b981;
            --accent-warning: #f59e0b;
            --accent-danger: #ef4444;
            --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
            --font-mono: 'Fira Code', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-sans);
            background-color: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.7;
            overflow-x: hidden;
        }

        header.top-navbar {
            position: fixed; top: 0; left: 0; right: 0; height: 64px;
            background: rgba(11, 19, 41, 0.85); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; z-index: 1000;
        }

        .brand-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: #fff; font-weight: 800; font-size: 1.25rem; letter-spacing: -0.025em; }
        .brand-badge { background: linear-gradient(135deg, var(--accent-primary), var(--accent-cyan)); padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .search-box { position: relative; width: 320px; }
        .search-box input { width: 100%; padding: 8px 16px 8px 36px; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); font-size: 0.875rem; outline: none; transition: all 0.2s; }
        .search-box input:focus { border-color: var(--accent-primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25); }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.875rem; }

        .layout-wrapper { display: flex; margin-top: 64px; min-height: calc(100vh - 64px); }
        aside.sidebar { width: 280px; background-color: var(--bg-sidebar); border-right: 1px solid var(--border-color); position: fixed; top: 64px; bottom: 0; overflow-y: auto; padding: 24px 16px; }
        .nav-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; margin-bottom: 12px; padding-left: 12px; }
        .nav-list { list-style: none; display: flex; flex-direction: column; gap: 4px; }
        .nav-item a { display: block; padding: 8px 12px; color: var(--text-secondary); text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; transition: all 0.15s ease-in-out; }
        .nav-item a:hover { background-color: rgba(255, 255, 255, 0.05); color: var(--text-primary); }
        .nav-item.active a { background-color: rgba(99, 102, 241, 0.15); color: #818cf8; font-weight: 600; border-left: 3px solid var(--accent-primary); }

        main.main-content { margin-left: 280px; flex: 1; padding: 40px 60px 80px; max-width: 960px; }
        .docs-body h1 { font-size: 2.25rem; font-weight: 800; margin-bottom: 24px; letter-spacing: -0.03em; background: linear-gradient(135deg, #ffffff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .docs-body h2 { font-size: 1.5rem; font-weight: 700; margin-top: 36px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color); color: #f1f5f9; }
        .docs-body h3 { font-size: 1.2rem; font-weight: 600; margin-top: 28px; margin-bottom: 12px; color: #e2e8f0; }

        .heading-anchor { color: var(--text-muted); text-decoration: none; opacity: 0; transition: opacity 0.2s; font-size: 0.85em; margin-left: 6px; }
        .docs-body h2:hover .heading-anchor, .docs-body h3:hover .heading-anchor { opacity: 1; }
        .docs-body p { margin-bottom: 18px; color: #cbd5e1; font-size: 1.025rem; }
        .docs-body ul, .docs-body ol { margin-bottom: 20px; padding-left: 24px; color: #cbd5e1; }
        .docs-body li { margin-bottom: 8px; }
        .docs-body a.docs-link { color: var(--accent-cyan); text-decoration: none; }
        .docs-body a.docs-link:hover { text-decoration: underline; }
        .docs-body code.inline-code { font-family: var(--font-mono); background-color: rgba(255, 255, 255, 0.08); color: #f472b6; padding: 2px 6px; border-radius: 4px; font-size: 0.875em; }

        .code-block { margin: 24px 0; background-color: var(--bg-code); border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4); }
        .code-header { display: flex; justify-content: space-between; align-items: center; background-color: #0f172a; padding: 8px 16px; border-bottom: 1px solid var(--border-color); }
        .code-lang { font-family: var(--font-mono); font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; }
        .copy-btn { background: rgba(255, 255, 255, 0.08); border: none; color: var(--text-secondary); padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; cursor: pointer; transition: all 0.2s; }
        .copy-btn:hover { background: var(--accent-primary); color: #fff; }
        .code-block pre { padding: 18px; overflow-x: auto; margin: 0; }
        .code-block code { background: none; color: #e2e8f0; padding: 0; font-size: 0.9rem; line-height: 1.6; font-family: var(--font-mono); }

        .alert { margin: 24px 0; padding: 16px 20px; border-radius: 8px; border-left: 4px solid var(--accent-primary); background-color: rgba(30, 41, 59, 0.6); }
        .alert-title { font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
        .alert-note { border-left-color: var(--accent-primary); }
        .alert-note .alert-title { color: #818cf8; }
        .alert-tip { border-left-color: var(--accent-green); }
        .alert-tip .alert-title { color: #34d399; }
        .alert-warning { border-left-color: var(--accent-warning); }
        .alert-warning .alert-title { color: #fbbf24; }
        .alert-important, .alert-caution { border-left-color: var(--accent-danger); }
        .alert-important .alert-title, .alert-caution .alert-title { color: #f87171; }

        .table-container { overflow-x: auto; margin: 24px 0; }
        .docs-table { width: 100%; border-collapse: collapse; font-size: 0.925rem; }
        .docs-table th, .docs-table td { padding: 12px 16px; border: 1px solid var(--border-color); text-align: left; }
        .docs-table th { background-color: #1e293b; color: var(--text-primary); font-weight: 600; }
        .docs-table tr:nth-child(even) { background-color: rgba(255, 255, 255, 0.02); }

        .docs-pagination { display: flex; justify-content: space-between; margin-top: 60px; padding-top: 24px; border-top: 1px solid var(--border-color); gap: 16px; }
        .pagination-card { flex: 1; padding: 16px 20px; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; transition: all 0.2s; }
        .pagination-card:hover { border-color: var(--accent-primary); transform: translateY(-2px); }
        .pagination-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 4px; }
        .pagination-title { font-size: 1rem; color: var(--accent-cyan); font-weight: 600; }
    </style>
</head>
<body>

    <header class="top-navbar">
        <a href="/docs/01-introduction" class="brand-logo">
            <span>NexusPHP</span>
            <span class="brand-badge">v1.0 Release</span>
        </a>
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="docSearch" placeholder="Search documentation..." onkeyup="filterDocs()">
        </div>
    </header>

    <div class="layout-wrapper">
        <aside class="sidebar">
            <div class="nav-title">Documentation Guide</div>
            <ul class="nav-list" id="navList">
                <?php foreach ($sidebarNav as $navItem): ?>
                    <li class="nav-item <?= ($navItem['slug'] === $currentSlug) ? 'active' : '' ?>">
                        <a href="/docs/<?= e($navItem['slug']) ?>">
                            <?= e($navItem['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="main-content">
            <article class="docs-body">
                <?= $htmlContent ?>
            </article>

            <nav class="docs-pagination">
                <?php if (!empty($prevDoc)): ?>
                    <a href="/docs/<?= e($prevDoc['slug']) ?>" class="pagination-card">
                        <span class="pagination-label">← Previous</span>
                        <span class="pagination-title"><?= e($prevDoc['title']) ?></span>
                    </a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <?php if (!empty($nextDoc)): ?>
                    <a href="/docs/<?= e($nextDoc['slug']) ?>" class="pagination-card" style="text-align: right;">
                        <span class="pagination-label">Next →</span>
                        <span class="pagination-title"><?= e($nextDoc['title']) ?></span>
                    </a>
                <?php endif; ?>
            </nav>
        </main>
    </div>

    <script>
        function filterDocs() {
            var input = document.getElementById('docSearch');
            var filter = input.value.toLowerCase();
            var ul = document.getElementById('navList');
            var li = ul.getElementsByTagName('li');

            for (var i = 0; i < li.length; i++) {
                var a = li[i].getElementsByTagName('a')[0];
                var txtValue = a.textContent || a.innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    li[i].style.display = "";
                } else {
                    li[i].style.display = "none";
                }
            }
        }

        function copyCode(button) {
            var code = button.parentElement.nextElementSibling.querySelector('code').innerText;
            navigator.clipboard.writeText(code).then(function() {
                var originalText = button.innerText;
                button.innerText = 'Copied!';
                button.style.background = '#10b981';
                button.style.color = '#fff';
                setTimeout(function() {
                    button.innerText = originalText;
                    button.style.background = '';
                    button.style.color = '';
                }, 2000);
            });
        }
    </script>
</body>
</html>
