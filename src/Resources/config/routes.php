<?php

use PhpLlm\McpBundle\Controller\McpController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('_mcp_sse', '/sse')
        ->controller([McpController::class, 'sse'])
        ->methods(['GET'])
    ;
    $routes->add('_mcp_messages', '/messages/{id}')
        ->controller([McpController::class, 'messages'])
        ->methods(['POST'])
    ;
};
