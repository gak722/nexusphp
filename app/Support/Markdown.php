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

        $html = preg_replace('/<\/code><\/pre>/i', '</code></pre></div>', $html);

        // 4. Post-process internal doc links (.md -> /docs/slug)
        $html = preg_replace_callback('/href="([0-9]{2}-[a-z0-9\-]+\.md)(#[^"]*)?"/i', function ($matches) {
            $file = basename($matches[1], '.md');
            $anchor = $matches[2] ?? '';
            return sprintf('href="/docs/%s%s"', $file, $anchor);
        }, $html);

        return $html;
    }
}

