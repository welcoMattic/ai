<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Together;

use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Dynamic catalog fetching the model list from the Together API (`GET /v1/models`).
 *
 * The endpoint only exposes a coarse `type` per model (chat, language, code, image,
 * embedding, rerank, audio, transcribe, video, moderation), so capabilities are derived
 * from that type. Types this bridge has no endpoint for - video above all - stay text-only,
 * so invoking one fails on the client instead of promising a capability it cannot serve.
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class ModelCatalog extends AbstractModelCatalog
{
    private bool $modelsAreLoaded = false;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
        $this->models = [];
    }

    public function getModel(string $modelName): Model
    {
        $this->preloadRemoteModels();

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

        $response = $this->httpClient->request('GET', '/v1/models');

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException(\sprintf('Cannot connect to the Together API: "%s".', $e->getMessage()), previous: $e);
        }

        if (200 !== $statusCode) {
            throw new RuntimeException(\sprintf('Cannot retrieve models from the Together API (Status code: %d).', $statusCode));
        }

        $catalog = [];

        foreach ($response->toArray() as $model) {
            if (!\is_array($model)) {
                continue;
            }

            if (!isset($model['id'], $model['type']) || !\is_string($model['id']) || !\is_string($model['type'])) {
                continue;
            }

            $catalog[$model['id']] = [
                'class' => Together::class,
                'capabilities' => $this->capabilitiesForType($model['type']),
            ];
        }

        ksort($catalog);

        $this->models = $catalog;
        $this->modelsAreLoaded = true;
    }

    /**
     * @return list<Capability>
     */
    private function capabilitiesForType(string $type): array
    {
        return match ($type) {
            'chat' => [
                Capability::INPUT_MESSAGES,
                Capability::OUTPUT_TEXT,
                Capability::OUTPUT_STREAMING,
                Capability::OUTPUT_STRUCTURED,
                Capability::TOOL_CALLING,
            ],
            'language', 'code' => [
                Capability::INPUT_MESSAGES,
                Capability::OUTPUT_TEXT,
                Capability::OUTPUT_STREAMING,
            ],
            'image' => [
                Capability::INPUT_TEXT,
                Capability::OUTPUT_IMAGE,
                Capability::TEXT_TO_IMAGE,
            ],
            'embedding' => [
                Capability::INPUT_TEXT,
                Capability::EMBEDDINGS,
            ],
            'rerank' => [
                Capability::INPUT_TEXT,
                Capability::RERANKING,
            ],
            'audio' => [
                Capability::INPUT_TEXT,
                Capability::TEXT_TO_SPEECH,
                Capability::OUTPUT_AUDIO,
            ],
            'transcribe' => [
                Capability::INPUT_AUDIO,
                Capability::SPEECH_TO_TEXT,
                Capability::OUTPUT_TEXT,
            ],
            'moderation' => [
                Capability::INPUT_MESSAGES,
                Capability::OUTPUT_TEXT,
            ],
            default => [
                Capability::INPUT_TEXT,
            ],
        };
    }
}
