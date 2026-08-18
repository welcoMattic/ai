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

use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Exception\AuthenticationException;
use Symfony\AI\Platform\Exception\RateLimitExceededException;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Exception\ServerException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Model catalog discovering the models enabled for the workspace from the Orq.ai
 * router `/models` endpoint.
 *
 * The endpoint only reports identity data (`id`, `owned_by`), so capabilities are
 * derived from the model name: identifiers containing "embed" are mapped to
 * `EmbeddingsModel`, everything else to `CompletionsModel`, which is what the
 * generic model clients need to route a request.
 *
 * @see https://docs.orq.ai/reference/models/list-router-models
 *
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ModelApiCatalog extends AbstractModelCatalog
{
    private bool $modelsAreLoaded = false;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[\SensitiveParameter] private readonly ?string $apiKey = null,
        private readonly string $baseUrl = Factory::DEFAULT_BASE_URL,
    ) {
        $this->models = [];
    }

    public function getModel(string $modelName): Model
    {
        $this->preloadRemoteModels();

        return parent::getModel($modelName);
    }

    /**
     * @return array<string, array{class: class-string, capabilities: list<Capability>}>
     */
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

        // Flagged only once the listing succeeded, so a transient failure does not leave the
        // catalog empty for good and report every model as unknown afterwards.
        $this->models = [...$this->models, ...$this->fetchRemoteModels()];
        $this->modelsAreLoaded = true;
    }

    /**
     * @return iterable<string, array{class: class-string<Model>, capabilities: list<Capability>}>
     */
    private function fetchRemoteModels(): iterable
    {
        $response = $this->httpClient->request('GET', rtrim($this->baseUrl, '/').'/models', [
            'auth_bearer' => $this->apiKey,
        ]);

        // The HTTP client would throw its own exception on the first read of a failed response.
        // Translate the status into a platform exception first, like the result converters do,
        // so callers only ever have to catch Symfony\AI\Platform\Exception\ExceptionInterface.
        $statusCode = $response->getStatusCode();

        if (200 !== $statusCode) {
            $message = json_decode($response->getContent(false), true)['error']['message'] ?? 'Unknown error.';

            throw match (true) {
                401 === $statusCode => new AuthenticationException($message),
                429 === $statusCode => new RateLimitExceededException(null, $message),
                $statusCode >= 500 => new ServerException($statusCode, $message),
                default => new RuntimeException(\sprintf('Failed to list the models of the Orq.ai router (HTTP %d): "%s".', $statusCode, $message)),
            };
        }

        foreach ($response->toArray()['data'] ?? [] as $model) {
            $id = $model['id'] ?? null;
            if (null === $id) {
                continue;
            }

            if (str_contains(strtolower($id), 'embed')) {
                yield $id => [
                    'class' => EmbeddingsModel::class,
                    'capabilities' => [Capability::INPUT_TEXT, Capability::EMBEDDINGS],
                ];

                continue;
            }

            yield $id => [
                'class' => CompletionsModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::OUTPUT_TEXT,
                    Capability::OUTPUT_STREAMING,
                    Capability::OUTPUT_STRUCTURED,
                    Capability::TOOL_CALLING,
                ],
            ];
        }
    }
}
