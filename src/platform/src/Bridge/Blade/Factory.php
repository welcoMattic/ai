<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade;

use Symfony\AI\Platform\Bridge\Generic;
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
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class Factory
{
    private const BASE_URL = 'https://api.models.blade.sh';

    /**
     * @param non-empty-string $name
     */
    public static function createProvider(
        #[\SensitiveParameter] string $apiKey,
        ?HttpClientInterface $httpClient = null,
        ModelCatalogInterface $modelCatalog = new ModelCatalog(),
        ?Contract $contract = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        string $name = 'blade',
    ): ProviderInterface {
        $httpClient = $httpClient instanceof EventSourceHttpClient ? $httpClient : new EventSourceHttpClient($httpClient);

        $modelClients = [
            new Generic\Completions\ModelClient($httpClient, self::BASE_URL, $apiKey),
            new Generic\Embeddings\ModelClient($httpClient, self::BASE_URL, $apiKey),
            new SpeechToText\ModelClient($httpClient, self::BASE_URL, $apiKey),
            new TextToSpeech\ModelClient($httpClient, self::BASE_URL, $apiKey),
            new ImageGeneration\ModelClient($httpClient, self::BASE_URL, $apiKey),
        ];
        $resultConverters = [
            new Generic\Completions\ResultConverter(),
            new Generic\Embeddings\ResultConverter(),
            new SpeechToText\ResultConverter(),
            new TextToSpeech\ResultConverter(),
            new ImageGeneration\ResultConverter(),
        ];

        return new Provider(
            $name,
            $modelClients,
            $resultConverters,
            $modelCatalog,
            $contract ?? Contract::create([new SpeechToText\AudioNormalizer()]),
            $eventDispatcher,
        );
    }

    /**
     * @param non-empty-string $name
     */
    public static function createPlatform(
        #[\SensitiveParameter] string $apiKey,
        ?HttpClientInterface $httpClient = null,
        ModelCatalogInterface $modelCatalog = new ModelCatalog(),
        ?Contract $contract = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        string $name = 'blade',
        ?ModelRouterInterface $modelRouter = null,
    ): Platform {
        return new Platform(
            [self::createProvider($apiKey, $httpClient, $modelCatalog, $contract, $eventDispatcher, $name)],
            $modelRouter ?? new CatalogBasedModelRouter(),
            $eventDispatcher,
        );
    }
}
