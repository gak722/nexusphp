<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\Response;
use App\Support\Markdown;

class DocsController extends Controller
{
    public function show(string $slug = '01-introduction'): Response
    {
        $docsDir = app()->basePath('resources/docs');
        if (!is_dir($docsDir)) {
            mkdir($docsDir, 0755, true);
        }

        // Scan all markdown files
        $files = glob($docsDir . '/*.md');
        sort($files);

        $sidebarNav = [];
        $docsList = [];

        foreach ($files as $filePath) {
            $filename = basename($filePath, '.md');
            $content = file_get_contents($filePath);

            // Extract title from first H1 if present
            $title = $filename;
            if (preg_match('/^#\s+(.*)$/m', $content, $matches)) {
                $title = trim($matches[1]);
            } else {
                $title = ucwords(str_replace('-', ' ', preg_replace('/^\d+-/', '', $filename)));
            }

            $item = [
                'slug' => $filename,
                'title' => $title,
                'path' => $filePath,
            ];

            $sidebarNav[] = $item;
            $docsList[$filename] = $item;
        }

        // Clean slug
        $slug = preg_replace('/\.md$/', '', $slug);
        if (empty($slug) || !isset($docsList[$slug])) {
            // Default or fallback to first doc if slug not found
            $slug = !empty($sidebarNav) ? $sidebarNav[0]['slug'] : '01-introduction';
        }

        $targetFile = $docsDir . '/' . $slug . '.md';

        if (!file_exists($targetFile)) {
            return $this->response('<h1>404 Document Not Found</h1><p>The requested documentation page does not exist.</p>', 404);
        }

        $rawMarkdown = file_get_contents($targetFile);
        $htmlContent = Markdown::parse($rawMarkdown);

        // Find current index, previous, and next doc
        $currentIndex = -1;
        foreach ($sidebarNav as $index => $navItem) {
            if ($navItem['slug'] === $slug) {
                $currentIndex = $index;
                break;
            }
        }

        $prevDoc = $currentIndex > 0 ? $sidebarNav[$currentIndex - 1] : null;
        $nextDoc = ($currentIndex >= 0 && $currentIndex < count($sidebarNav) - 1) ? $sidebarNav[$currentIndex + 1] : null;
        $currentTitle = $docsList[$slug]['title'] ?? 'Documentation';

        return $this->view('docs.show', [
            'htmlContent' => $htmlContent,
            'currentTitle' => $currentTitle,
            'currentSlug' => $slug,
            'sidebarNav' => $sidebarNav,
            'prevDoc' => $prevDoc,
            'nextDoc' => $nextDoc,
        ]);
    }
}
