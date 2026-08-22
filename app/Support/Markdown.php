<?php
declare(strict_types=1);

namespace App\Support;

require_once dirname(__DIR__, 2) . '/framework/Support/Parsedown.php';

use Parsedown;

/**
 * Markdown Parser for NexusPHP using standalone Parsedown.
 */
class Markdown
{
    protected Parsedown $parsedown;

    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->parsedown->setSafeMode(false);
    }

    /**
     * Convert standard GFM Markdown into clean, accessible HTML using Parsedown.
     */
    public static function parse(string $markdown): string
    {
        $parser = new static();
        return $parser->render($markdown);
    }

    public function render(string $markdown): string
    {
        // 1. Normalize line endings
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);

        // 2. Parse using Parsedown
        $html = $this->parsedown->text($markdown);

        // 3. Post-process code blocks for UI copy buttons & syntax language badges
        $html = preg_replace_callback('/<pre><code class="language-([a-zA-Z0-9_\-]+)">/i', function ($matches) {
            $lang = $matches[1];
            return '<div class="code-block" data-language="' . e($lang) . '">'
                . '<div class="code-header">'
                . '<span class="code-lang">' . strtoupper(e($lang)) . '</span>'
                . '<button class="copy-btn" onclick="copyCode(this)" aria-label="Copy code block">Copy</button>'
                . '</div>'
                . '<pre><code class="language-' . e($lang) . '">';
        }, $html);

        // Wrap pre code blocks with code-block container when language is missing
        $html = preg_replace_callback('/<pre>(?!<code class="language-)/i', function () {
            return '<div class="code-block"><div class="code-header"><span class="code-lang">CODE</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div><pre>';
        }, $html);

        $html = preg_replace('/<\/code><\/pre>/i', '</code></pre></div>', $html);


        // 4. Post-process GitHub alert callouts (> [!NOTE], etc.)
        $html = preg_replace_callback('/<blockquote\b[^>]*>\s*<p>\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*(.*?)<\/p>\s*<\/blockquote>/is', function ($matches) {
            $type = strtolower($matches[1]);
            $body = $matches[2];
            $styles = [
                'note' => ['border' => 'border-hostinger-purple', 'bg' => 'bg-hostinger-purple/10', 'text' => 'text-purple-300', 'badge' => 'badge-primary', 'icon' => 'ℹ️'],
                'tip' => ['border' => 'border-emerald-500', 'bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-300', 'badge' => 'badge-success', 'icon' => '💡'],
                'important' => ['border' => 'border-cyan-500', 'bg' => 'bg-cyan-500/10', 'text' => 'text-cyan-300', 'badge' => 'badge-info', 'icon' => '📌'],
                'warning' => ['border' => 'border-amber-500', 'bg' => 'bg-amber-500/10', 'text' => 'text-amber-300', 'badge' => 'badge-warning', 'icon' => '⚠️'],
                'caution' => ['border' => 'border-rose-500', 'bg' => 'bg-rose-500/10', 'text' => 'text-rose-300', 'badge' => 'badge-error', 'icon' => '🚫'],
            ];
            $s = $styles[$type] ?? $styles['note'];
            return sprintf(
                '<div class="my-6 p-4 rounded-xl border-l-4 %s %s shadow-lg"><div class="flex items-center gap-2 font-bold uppercase tracking-wider text-xs %s mb-2"><span>%s</span> %s</div><div class="text-sm text-gray-300">%s</div></div>',
                $s['border'],
                $s['bg'],
                $s['text'],
                $s['icon'],
                ucfirst($type),
                $body
            );
        }, $html);


        // 5. Post-process internal doc links (.md -> /docs/slug)
        $html = preg_replace_callback('/href="([0-9]{2}-[a-z0-9\-]+\.md)(#[^"]*)?"/i', function ($matches) {
            $file = basename($matches[1], '.md');
            $anchor = $matches[2] ?? '';
            return sprintf('href="/docs/%s%s"', $file, $anchor);
        }, $html);

        return $html;
    }
}


