<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\OrqAi;

use Symfony\AI\Platform\Bridge\Generic;
use Symfony\AI\Platform\Bridge\Generic\FallbackModelCatalog;
use Symfony\AI\Platform\Bridge\OrqAi\Completions\ResultConverter as CompletionsResultConverter;
use Symfony\AI\Platform\Contract;
use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use Symfony\AI\Platform\ModelRouter\CatalogBasedModelRouter;
use Symfony\AI\Platform\ModelRouterInterface;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Provider;
use Symfony\AI\Platform\ProviderInterface;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Orq.ai exposes its AI gateway through an OpenAI-compatible router, so the generic
 * completions and embeddings clients can be reused as is.
 *
 * @see https://docs.orq.ai/docs/ai-gateway/features/openai-compatible-api
 *
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class Factory
{
    public const DEFAULT_BASE_URL = 'https://api.orq.ai/v3/router';

    /**
     * @param non-empty-string $name
     */
    public static function createProvider(
        #[\SensitiveParameter] string $apiKey,
        ?HttpClientInterface $httpClient = null,
        ModelCatalogInterface $modelCatalog = new FallbackModelCatalog(),
        ?Contract $contract = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        string $name = 'orqai',
        string $baseUrl = self::DEFAULT_BASE_URL,
    ): ProviderInterface {
        $httpClient = $httpClient instanceof EventSourceHttpClient ? $httpClient : new EventSourceHttpClient($httpClient);

        $modelClients = [
            new Generic\Completions\ModelClient($httpClient, $baseUrl, $apiKey, '/chat/completions'),
            new Generic\Embeddings\ModelClient($httpClient, $baseUrl, $apiKey, '/embeddings'),
        ];
        $resultConverters = [
            new CompletionsResultConverter(),
            new Generic\Embeddings\ResultConverter(),
        ];

        return new Provider($name, $modelClients, $resultConverters, $modelCatalog, $contract, $eventDispatcher);
    }

    /**
     * @param non-empty-string $name
     */
    public static function createPlatform(
        #[\SensitiveParameter] string $apiKey,
        ?HttpClientInterface $httpClient = null,
        ModelCatalogInterface $modelCatalog = new FallbackModelCatalog(),
        ?Contract $contract = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        string $name = 'orqai',
        ?ModelRouterInterface $modelRouter = null,
        string $baseUrl = self::DEFAULT_BASE_URL,
    ): Platform {
        return new Platform(
            [self::createProvider($apiKey, $httpClient, $modelCatalog, $contract, $eventDispatcher, $name, $baseUrl)],
            $modelRouter ?? new CatalogBasedModelRouter(),
            $eventDispatcher,
        );
    }
}
