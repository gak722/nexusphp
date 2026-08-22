<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * HTTP JsonResponse Subclass
 */
class JsonResponse extends Response
{
    public function __construct(mixed $data = [], int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        $content = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($content === false) {
            $content = '{}';
        }
        parent::__construct($content, $status, $headers);
    }
}
