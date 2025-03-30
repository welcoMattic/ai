<?php

declare(strict_types=1);

namespace PhpLlm\McpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('mcp');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('app')->defaultValue('app')->end()
                ->scalarNode('version')->defaultValue('0.0.1')->end()
                // ->arrayNode('servers')
                //     ->useAttributeAsKey('name')
                //     ->arrayPrototype()
                //         ->children()
                //             ->enumNode('transport')
                //                 ->values(['stdio', 'sse'])
                //                 ->isRequired()
                //             ->end()
                //             ->arrayNode('stdio')
                //                 ->children()
                //                     ->scalarNode('command')->isRequired()->end()
                //                     ->arrayNode('arguments')
                //                         ->scalarPrototype()->end()
                //                         ->defaultValue([])
                //                     ->end()
                //                 ->end()
                //             ->end()
                //             ->arrayNode('sse')
                //                 ->children()
                //                     ->scalarNode('url')->isRequired()->end()
                //                 ->end()
                //             ->end()
                //         ->end()
                //         ->validate()
                //             ->ifTrue(function ($v) {
                //                 if ('stdio' === $v['transport'] && !isset($v['stdio'])) {
                //                     return true;
                //                 }
                //                 if ('sse' === $v['transport'] && !isset($v['sse'])) {
                //                     return true;
                //                 }
                //
                //                 return false;
                //             })
                //             ->thenInvalid('When transport is "%s", you must configure the corresponding section.')
                //         ->end()
                //     ->end()
                // ->end()
                ->arrayNode('client_transports')
                    ->children()
                        ->booleanNode('stdio')->defaultFalse()->end()
                        ->booleanNode('sse')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
