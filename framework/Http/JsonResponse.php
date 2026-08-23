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
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        $encoded = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        parent::__construct($encoded, $status, $headers);
    }

    public function setData(mixed $data): static
    {
        $this->content = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        return $this;
    }
}
