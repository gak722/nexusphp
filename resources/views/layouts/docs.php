<!DOCTYPE html>
<html lang="en" class="dark h-full bg-[#120E16] text-[#D1D5DB] antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($currentTitle ?? 'NexusPHP Framework') ?> - NexusPHP Documentation</title>
    <meta name="description" content="Official documentation for NexusPHP - zero-dependency, ultra-high performance PHP 8.4 framework.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        hostinger: {
                            purple: '#673DE6',
                            purpleDark: '#5429D2',
                            purpleLight: '#8C66FF',
                            purpleGlow: 'rgba(103, 61, 230, 0.25)',
                            violet: '#2F1C44',
                            deepBg: '#120E16',
                            cardBg: '#1A1423',
                            sidebarBg: '#16101F',
                            border: '#2C223A',
                            borderHover: '#423358',
                            muted: '#9CA3AF',
                            text: '#E5E7EB'
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"Fira Code"', 'monospace']
                    }
                }
            }
        }
    </script>
    <!-- DaisyUI CDN -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />

    <style>
        /* Exact Laravel documentation typography & styling with Hostinger palette */
        ::selection { background-color: #673DE6; color: #FFFFFF; }
        
        .prose h1 {
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
            color: #FFFFFF;
            line-height: 1.2;
        }
        .prose h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #2C223A;
            color: #F3F4F6;
            scroll-margin-top: 5rem;
        }
        .prose h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            color: #E5E7EB;
            scroll-margin-top: 5rem;
        }
        .prose p {
            margin-bottom: 1.25rem;
            color: #9CA3AF;
            line-height: 1.8;
            font-size: 1rem;
        }
        .prose ul, .prose ol {
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
            color: #9CA3AF;
        }
        .prose ul { list-style-type: disc; }
        .prose ol { list-style-type: decimal; }
        .prose li { margin-bottom: 0.5rem; }
        .prose a {
            color: #8C66FF;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid rgba(140, 102, 255, 0.3);
            transition: all 0.15s ease;
        }
        .prose a:hover {
            color: #FFFFFF;
            border-bottom-color: #8C66FF;
            background-color: rgba(103, 61, 230, 0.15);
        }
        .prose code:not(pre code) {
            font-family: 'Fira Code', monospace;
            background-color: #1A1423;
            color: #D8B4FE;
            padding: 0.2rem 0.45rem;
            border-radius: 0.375rem;
            font-size: 0.85em;
            border: 1px solid #2C223A;
        }

        .code-block {
            margin: 1.75rem 0;
            background-color: #16101F;
            border: 1px solid #2C223A;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.6);
        }
        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #1A1423;
            padding: 0.625rem 1rem;
            border-bottom: 1px solid #2C223A;
        }
        .code-lang {
            font-family: 'Fira Code', monospace;
            font-size: 0.75rem;
            color: #8C66FF;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .copy-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #2C223A;
            color: #9CA3AF;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .copy-btn:hover {
            background: #673DE6;
            color: #FFFFFF;
            border-color: #673DE6;
            box-shadow: 0 0 12px rgba(103, 61, 230, 0.4);
        }
        .code-block pre {
            padding: 1.25rem;
            overflow-x: auto;
            margin: 0;
            background: transparent;
        }
        .code-block code {
            font-family: 'Fira Code', monospace;
            font-size: 0.875rem;
            line-height: 1.75;
            color: #E5E7EB;
        }

        /* Tables & Typography container enhancements */
        .prose table {
            width: 100%;
            margin: 1.75rem 0;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #2C223A;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .prose th {
            background-color: #1A1423;
            color: #FFFFFF;
            font-weight: 700;
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            border-bottom: 1px solid #2C223A;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .prose td {
            padding: 0.875rem 1rem;
            color: #9CA3AF;
            font-size: 0.9rem;
            border-bottom: 1px solid #2C223A;
            line-height: 1.6;
        }
        .prose tr:last-child td {
            border-bottom: none;
        }
        .prose tr:nth-child(even) td {
            background-color: rgba(255, 255, 255, 0.015);
        }

        .prose blockquote {
            border-left: 4px solid #673DE6;
            background: rgba(103, 61, 230, 0.08);
            padding: 1rem 1.25rem;
            border-radius: 0 0.75rem 0.75rem 0;
            margin: 1.5rem 0;
            color: #D1D5DB;
        }
        .prose blockquote p {
            margin-bottom: 0;
        }

        /* Custom Scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #120E16; }
        ::-webkit-scrollbar-thumb { background: #2C223A; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #673DE6; }
    </style>

</head>
<body class="min-h-screen flex flex-col bg-[#120E16] font-sans antialiased text-hostinger-muted selection:bg-hostinger-purple selection:text-white">

    <!-- Top Navigation Header (Laravel Docs Style) -->
    <header class="sticky top-0 z-50 w-full backdrop-blur-xl bg-[#120E16]/90 border-b border-hostinger-border shadow-sm shadow-black/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            
            <!-- Left Branding -->
            <div class="flex items-center gap-4 sm:gap-6">
                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileSidebar()" class="lg:hidden p-2 rounded-lg text-hostinger-muted hover:text-white hover:bg-hostinger-cardBg transition-colors" aria-label="Toggle Navigation">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <a href="/docs/01-introduction" class="flex items-center gap-3 group">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-hostinger-purple via-hostinger-purpleDark to-hostinger-violet flex items-center justify-center text-white font-black text-xl shadow-lg shadow-hostinger-purple/25 group-hover:scale-105 transition-all duration-200">
                        N
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-white text-base sm:text-lg tracking-tight group-hover:text-hostinger-purpleLight transition-colors">NexusPHP</span>
                            <span class="hidden sm:inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-hostinger-purple/20 text-hostinger-purpleLight border border-hostinger-purple/30">Docs</span>
                        </div>
                        <span class="text-[10px] text-hostinger-muted tracking-widest uppercase font-semibold hidden sm:inline-block">High-Performance PHP Framework</span>
                    </div>
                </a>
            </div>

            <!-- Central Search Bar (Laravel Style) -->
            <div class="flex-1 max-w-md hidden md:block">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-hostinger-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="docSearch" onkeyup="filterDocs()" 
                           class="w-full pl-10 pr-12 py-2 text-xs bg-hostinger-cardBg text-white placeholder-hostinger-muted border border-hostinger-border rounded-xl focus:outline-none focus:border-hostinger-purple focus:ring-2 focus:ring-hostinger-purple/30 transition-all shadow-inner"
                           placeholder="Search documentation (Press '/' to focus)...">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono text-hostinger-muted bg-hostinger-deepBg border border-hostinger-border rounded">⌘K</kbd>
                    </div>
                </div>
            </div>

            <!-- Right Controls: Version Select & Repository Links -->
            <div class="flex items-center gap-3">
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-xs sm:btn-sm bg-hostinger-cardBg hover:bg-hostinger-sidebarBg border-hostinger-border text-white text-xs gap-1.5 normal-case font-medium rounded-xl">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        v1.0 (Latest)
                        <svg class="w-3.5 h-3.5 text-hostinger-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-2xl bg-hostinger-cardBg border border-hostinger-border rounded-xl w-44 mt-2 text-xs">
                        <li><a class="active bg-hostinger-purple text-white font-semibold">v1.0.0 (Current)</a></li>
                        <li><a class="text-hostinger-muted hover:text-white">v0.9-beta</a></li>
                    </ul>
                </div>

                <a href="https://github.com" target="_blank" rel="noopener" class="p-2 text-hostinger-muted hover:text-white rounded-xl hover:bg-hostinger-cardBg transition-colors" title="GitHub Repository">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
                    </svg>
                </a>
            </div>

        </div>
    </header>

    <div class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 flex">
        
        <!-- Left Sidebar Navigation (Laravel Style) -->
        <aside id="sidebarNav" class="w-64 shrink-0 hidden lg:block py-8 pr-6 border-r border-hostinger-border sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto">
            <div class="mb-4 text-xs font-bold uppercase tracking-wider text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-hostinger-purple shadow-sm shadow-hostinger-purple"></span>
                Documentation Guide
            </div>
            <ul class="space-y-1.5" id="navList">
                <?php foreach ($sidebarNav as $navItem): ?>
                    <?php $isActive = ($navItem['slug'] === $currentSlug); ?>
                    <li>
                        <a href="/docs/<?= e($navItem['slug']) ?>" 
                           class="group flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 <?= $isActive ? 'bg-hostinger-purple/20 text-white font-semibold border-l-4 border-hostinger-purple shadow-md shadow-hostinger-purple/10' : 'text-hostinger-muted hover:text-white hover:bg-hostinger-cardBg' ?>">
                            <span class="truncate"><?= e($navItem['title']) ?></span>
                            <?php if ($isActive): ?>
                                <svg class="ml-auto w-4 h-4 text-hostinger-purpleLight" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Main Documentation Body -->
        <main class="flex-1 min-w-0 py-10 lg:px-12 max-w-4xl">
            
            <!-- Breadcrumbs -->
            <div class="mb-6 flex items-center gap-2 text-xs text-hostinger-muted">
                <a href="/docs/01-introduction" class="hover:text-white transition-colors">Documentation</a>
                <span>/</span>
                <span class="text-hostinger-purpleLight font-semibold"><?= e($currentTitle) ?></span>
            </div>

            <!-- Rendered Markdown Content -->
            <article class="prose max-w-none">
                <?= $htmlContent ?>
            </article>

            <!-- Laravel Style Previous / Next Footer Pagination -->
            <nav class="mt-16 pt-8 border-t border-hostinger-border grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php if (!empty($prevDoc)): ?>
                    <a href="/docs/<?= e($prevDoc['slug']) ?>" 
                       class="group p-5 bg-hostinger-cardBg hover:bg-hostinger-sidebarBg border border-hostinger-border hover:border-hostinger-purple rounded-2xl transition-all flex flex-col shadow-sm hover:shadow-md hover:shadow-hostinger-purple/10">
                        <span class="text-xs font-bold text-hostinger-purpleLight uppercase tracking-wider mb-1 group-hover:-translate-x-1 transition-transform inline-flex items-center gap-1">
                            ← Previous Chapter
                        </span>
                        <span class="text-base font-bold text-white group-hover:text-hostinger-purpleLight transition-colors">
                            <?= e($prevDoc['title']) ?>
                        </span>
                    </a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <?php if (!empty($nextDoc)): ?>
                    <a href="/docs/<?= e($nextDoc['slug']) ?>" 
                       class="group p-5 bg-hostinger-cardBg hover:bg-hostinger-sidebarBg border border-hostinger-border hover:border-hostinger-purple rounded-2xl transition-all flex flex-col text-right shadow-sm hover:shadow-md hover:shadow-hostinger-purple/10">
                        <span class="text-xs font-bold text-hostinger-purpleLight uppercase tracking-wider mb-1 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1 justify-end">
                            Next Chapter →
                        </span>
                        <span class="text-base font-bold text-white group-hover:text-hostinger-purpleLight transition-colors">
                            <?= e($nextDoc['title']) ?>
                        </span>
                    </a>
                <?php endif; ?>
            </nav>
        </main>
    </div>

    <!-- Minimal Modern Footer -->
    <footer class="w-full border-t border-hostinger-border bg-hostinger-sidebarBg py-6 mt-12 text-xs text-hostinger-muted">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 rounded-lg bg-hostinger-purple flex items-center justify-center text-white font-bold text-xs">N</div>
                <span>NexusPHP Framework © <?= date('Y') ?>. Built with zero dependencies.</span>
            </div>
            <div class="flex items-center gap-6">
                <a href="/docs/01-introduction" class="hover:text-white transition-colors">Intro</a>
                <a href="/docs/02-installation" class="hover:text-white transition-colors">Installation</a>
                <a href="/docs/13-deployment" class="hover:text-white transition-colors">Hostinger Deployment</a>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileSidebar() {
            var sidebar = document.getElementById('sidebarNav');
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('fixed');
            sidebar.classList.toggle('inset-0');
            sidebar.classList.toggle('z-40');
            sidebar.classList.toggle('bg-[#120E16]');
            sidebar.classList.toggle('w-full');
            sidebar.classList.toggle('p-6');
        }

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
                button.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
                setTimeout(function() {
                    button.innerText = originalText;
                    button.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600');
                }, 2000);
            });
        }

        // Focus search bar on slash key press
        document.addEventListener('keydown', function(e) {
            if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
                e.preventDefault();
                document.getElementById('docSearch').focus();
            }
        });
    </script>
</body>
</html>



