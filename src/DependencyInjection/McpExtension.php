<?php

declare(strict_types=1);

namespace PhpLlm\McpBundle\DependencyInjection;

use PhpLlm\McpBundle\Command\McpCommand;
use PhpLlm\McpBundle\Controller\McpController;
use PhpLlm\McpBundle\Routing\RouteLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class McpExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('mcp.app', $config['app']);
        $container->setParameter('mcp.version', $config['version']);

        if (isset($config['client_transports'])) {
            $this->configureClient($config['client_transports'], $container);
        }
    }

    /**
     * @param array{stdio: bool, sse: bool} $transports
     */
    private function configureClient(array $transports, ContainerBuilder $container): void
    {
        if (!$transports['stdio'] && !$transports['sse']) {
            return;
        }

        if ($transports['stdio']) {
            $container->register('mcp.server.command', McpCommand::class)
                ->setAutowired(true)
                ->addTag('console.command');
        }

        if ($transports['sse']) {
            $container->register('mcp.server.controller', McpController::class)
                ->setAutowired(true)
                ->setPublic(true)
                ->addTag('controller.service_arguments');
        }

        $container->register('mcp.server.route_loader', RouteLoader::class)
            ->setArgument('$sseTransportEnabled', $transports['sse'])
            ->addTag('routing.route_loader');
    }
}
