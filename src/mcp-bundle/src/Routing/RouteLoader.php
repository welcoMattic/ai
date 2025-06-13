<?php

declare(strict_types=1);

namespace PhpLlm\McpBundle\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final readonly class RouteLoader
{
    public function __construct(
        private bool $sseTransportEnabled,
    ) {
    }

    public function __invoke(): RouteCollection
    {
        if (!$this->sseTransportEnabled) {
            return new RouteCollection();
        }

        $collection = new RouteCollection();

        $collection->add('_mcp_sse', new Route('/_mcp/sse', ['_controller' => ['mcp.server.controller', 'sse']], methods: ['GET']));
        $collection->add('_mcp_messages', new Route('/_mcp/messages/{id}', ['_controller' => ['mcp.server.controller', 'messages']], methods: ['POST']));

        return $collection;
    }
}
