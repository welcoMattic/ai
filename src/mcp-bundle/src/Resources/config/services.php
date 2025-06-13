<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\McpSdk\Message\Factory;
use PhpLlm\McpSdk\Server;
use PhpLlm\McpSdk\Server\JsonRpcHandler;
use PhpLlm\McpSdk\Server\NotificationHandler;
use PhpLlm\McpSdk\Server\NotificationHandler\InitializedHandler;
use PhpLlm\McpSdk\Server\RequestHandler;
use PhpLlm\McpSdk\Server\RequestHandler\InitializeHandler;
use PhpLlm\McpSdk\Server\RequestHandler\PingHandler;
use PhpLlm\McpSdk\Server\RequestHandler\ToolCallHandler;
use PhpLlm\McpSdk\Server\RequestHandler\ToolListHandler;
use PhpLlm\McpSdk\Server\Transport\Sse\Store\CachePoolStore;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
        ->instanceof(NotificationHandler::class)
            ->tag('mcp.server.notification_handler')
        ->instanceof(RequestHandler::class)
            ->tag('mcp.server.request_handler')

        ->set(InitializedHandler::class)
        ->set(InitializeHandler::class)
        ->args([
            '$name' => param('mcp.app'),
            '$version' => param('mcp.version'),
        ])
        ->set(PingHandler::class)
        ->set(ToolCallHandler::class)
        ->set(ToolListHandler::class)

        ->set('mcp.message_factory', Factory::class)
        ->set('mcp.server.json_rpc', JsonRpcHandler::class)
            ->args([
                '$messageFactory' => service('mcp.message_factory'),
                '$requestHandlers' => tagged_iterator('mcp.server.request_handler'),
                '$notificationHandlers' => tagged_iterator('mcp.server.notification_handler'),
            ])
        ->set('mcp.server', Server::class)
            ->args([
                '$jsonRpcHandler' => service('mcp.server.json_rpc'),
            ])
            ->alias(Server::class, 'mcp.server')
        ->set(CachePoolStore::class)
    ;
};
