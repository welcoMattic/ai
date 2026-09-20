<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Fireworks;

use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Discovers Fireworks models through the gateway control-plane API, deriving the capabilities
 * from the `kind` each model is filed under.
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class ModelCatalog extends AbstractModelCatalog
{
    public const DEFAULT_GATEWAY_ENDPOINT = 'https://api.fireworks.ai';

    /**
     * Kinds only ever served attached to another model's deployment, never invoked on their own.
     */
    private const NON_INVOCABLE_KINDS = ['DRAFT_ADDON', 'FLUMINA_ADDON'];

    private bool $modelsAreLoaded = false;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[\SensitiveParameter] private readonly string $apiKey,
        private readonly string $accountId = 'fireworks',
        private readonly ?string $gatewayEndpoint = null,
    ) {
        $this->models = [];
    }

    public function getModel(string $modelName): Model
    {
        $modelName = $this->canonicalize($modelName);

        if ('' !== $modelName) {
            $catalogKey = $this->parseModelName($modelName)['catalogKey'];

            if (!isset($this->models[$catalogKey]) && null !== $entry = $this->fetchRemoteModel($catalogKey)) {
                $this->models[$catalogKey] = $entry;
            }
        }

        return parent::getModel($modelName);
    }

    public function getModels(): array
    {
        $this->preloadRemoteModels();

        return parent::getModels();
    }

    private function preloadRemoteModels(): void
    {
        if ($this->modelsAreLoaded) {
            return;
        }

        $this->models = iterator_to_array($this->fetchRemoteModels());
        ksort($this->models);
        $this->modelsAreLoaded = true;
    }

    /**
     * Expands a bare model id to the qualified resource name: /v1/embeddings and /v1/rerank
     * reject the short form that chat completions accepts.
     */
    private function canonicalize(string $modelName): string
    {
        if ('' === $modelName || str_contains($modelName, '/')) {
            return $modelName;
        }

        [$name, $options] = array_pad(explode('?', $modelName, 2), 2, null);

        return \sprintf('accounts/%s/models/%s', $this->accountId, $name).(null === $options ? '' : '?'.$options);
    }

    /**
     * @return array{class: class-string<Model>, capabilities: list<Capability>}|null Null lets the parent
     *                                                                                raise ModelNotFoundException
     */
    private function fetchRemoteModel(string $modelName): ?array
    {
        $endpoint = $this->gatewayEndpoint ?? self::DEFAULT_GATEWAY_ENDPOINT;

        try {
            $response = $this->httpClient->request('GET', \sprintf('%s/v1/%s', $endpoint, $modelName), [
                'auth_bearer' => $this->apiKey,
            ]);

            if (404 === $response->getStatusCode()) {
                return null;
            }

            $model = $response->toArray();
        } catch (HttpExceptionInterface $e) {
            throw new RuntimeException(\sprintf('Cannot retrieve model "%s" from the Fireworks gateway: "%s".', $modelName, $e->getMessage()), previous: $e);
        }

        if (\in_array($model['kind'] ?? '', self::NON_INVOCABLE_KINDS, true)) {
            return null;
        }

        return [
            'class' => Fireworks::class,
            'capabilities' => $this->detectCapabilities($model),
        ];
    }

    /**
     * @return iterable<string, array{class: class-string<Model>, capabilities: list<Capability>}>
     */
    private function fetchRemoteModels(): iterable
    {
        $endpoint = $this->gatewayEndpoint ?? self::DEFAULT_GATEWAY_ENDPOINT;
        $pageToken = null;

        do {
            try {
                $response = $this->httpClient->request('GET', \sprintf('%s/v1/accounts/%s/models', $endpoint, $this->accountId), [
                    'auth_bearer' => $this->apiKey,
                    'query' => null === $pageToken ? ['pageSize' => 200] : ['pageSize' => 200, 'pageToken' => $pageToken],
                ]);

                $payload = $response->toArray();
            } catch (HttpExceptionInterface $e) {
                throw new RuntimeException(\sprintf('Cannot retrieve models from the Fireworks gateway: "%s".', $e->getMessage()), previous: $e);
            }

            $pageToken = $payload['nextPageToken'] ?? null;

            foreach ($payload['models'] ?? [] as $model) {
                if (\in_array($model['kind'] ?? '', self::NON_INVOCABLE_KINDS, true)) {
                    continue;
                }

                // Listing is discovery, so it skips what needs a deployment first; getModel() does not.
                if (!($model['supportsServerless'] ?? false)) {
                    continue;
                }

                yield $model['name'] => [
                    'class' => Fireworks::class,
                    'capabilities' => $this->detectCapabilities($model),
                ];
            }
        } while (null !== $pageToken && '' !== $pageToken);
    }

    /**
     * @param array<string, mixed> $model
     *
     * @return list<Capability>
     */
    private function detectCapabilities(array $model): array
    {
        $kind = $model['kind'] ?? null;

        if ('EMBEDDING_MODEL' === $kind) {
            // Rerankers share the embedding kind and are flagged nowhere else, only the name differs.
            if (str_contains($model['name'] ?? '', 'rerank')) {
                return [Capability::RERANKING];
            }

            return [Capability::INPUT_TEXT, Capability::EMBEDDINGS];
        }

        // Flumina serves the image models; labelling them keeps them off the completions endpoint.
        if ('FLUMINA_BASE_MODEL' === $kind) {
            return [Capability::INPUT_TEXT, Capability::TEXT_TO_IMAGE, Capability::OUTPUT_IMAGE];
        }

        $capabilities = [
            Capability::INPUT_MESSAGES,
            Capability::OUTPUT_TEXT,
            Capability::OUTPUT_STREAMING,
            Capability::OUTPUT_STRUCTURED,
        ];

        if ($model['supportsTools'] ?? false) {
            $capabilities[] = Capability::TOOL_CALLING;
        }

        if ($model['supportsImageInput'] ?? false) {
            $capabilities[] = Capability::INPUT_IMAGE;
        }

        return $capabilities;
    }
}
