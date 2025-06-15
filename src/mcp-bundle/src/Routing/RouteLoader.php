<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\McpBundle\Routing;

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
