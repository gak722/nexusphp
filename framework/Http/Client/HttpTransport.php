<?php
declare(strict_types=1);

namespace Nexus\Http\Client;

interface HttpTransport
{
    public function send(HttpRequest $request): HttpResponse;
}
