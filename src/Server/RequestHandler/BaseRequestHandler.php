<?php

declare(strict_types=1);

namespace PhpLlm\McpSdk\Server\RequestHandler;

use PhpLlm\McpSdk\Message\Request;
use PhpLlm\McpSdk\Server\RequestHandler;

abstract class BaseRequestHandler implements RequestHandler
{
    public function supports(Request $message): bool
    {
        return $message->method === $this->supportedMethod();
    }

    abstract protected function supportedMethod(): string;
}
