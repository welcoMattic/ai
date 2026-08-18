<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\OrqAi\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Bridge\OrqAi\ModelApiCatalog;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Exception\AuthenticationException;
use Symfony\AI\Platform\Exception\ModelNotFoundException;
use Symfony\AI\Platform\Exception\RateLimitExceededException;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Exception\ServerException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface as HttpResponse;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ModelApiCatalogTest extends TestCase
{
    public function testItRequestsTheRouterModelsEndpoint()
    {
        $callback = function (string $method, string $url, array $options): HttpResponse {
            $this->assertSame('GET', $method);
            $this->assertSame('https://api.orq.ai/v3/router/models', $url);
            $this->assertSame('Authorization: Bearer test-api-key', $options['normalized_headers']['authorization'][0]);

            return new JsonMockResponse($this->getModelsResponse());
        };

        $catalog = new ModelApiCatalog(new MockHttpClient([$callback]), 'test-api-key');

        $this->assertArrayHasKey('openai/gpt-4o', $catalog->getModels());
    }

    public function testItDoesNotRequestModelsOnConstruction()
    {
        $httpClient = new MockHttpClient(fn () => $this->fail('No HTTP request expected before the catalog is accessed.'));

        $catalog = new ModelApiCatalog($httpClient, 'test-api-key');

        $this->assertInstanceOf(ModelApiCatalog::class, $catalog);
    }

    public function testItLoadsModelsOnlyOnce()
    {
        $callCount = 0;
        $httpClient = new MockHttpClient(function () use (&$callCount) {
            ++$callCount;

            return new JsonMockResponse($this->getModelsResponse());
        });

        $catalog = new ModelApiCatalog($httpClient, 'test-api-key');
        $catalog->getModels();
        $catalog->getModels();
        $catalog->getModel('openai/gpt-4o');

        $this->assertSame(1, $callCount);
    }

    public function testItMapsChatModelsToCompletionsModel()
    {
        $catalog = $this->createCatalog();

        $model = $catalog->getModel('anthropic/claude-sonnet-4-5');

        $this->assertInstanceOf(CompletionsModel::class, $model);
        $this->assertSame('anthropic/claude-sonnet-4-5', $model->getName());
        $this->assertTrue($model->supports(Capability::INPUT_MESSAGES));
        $this->assertTrue($model->supports(Capability::OUTPUT_TEXT));
        $this->assertTrue($model->supports(Capability::OUTPUT_STREAMING));
        $this->assertTrue($model->supports(Capability::OUTPUT_STRUCTURED));
        $this->assertTrue($model->supports(Capability::TOOL_CALLING));
    }

    public function testItMapsEmbeddingModelsToEmbeddingsModel()
    {
        $catalog = $this->createCatalog();

        $model = $catalog->getModel('openai/text-embedding-3-small');

        $this->assertInstanceOf(EmbeddingsModel::class, $model);
        $this->assertTrue($model->supports(Capability::EMBEDDINGS));
        $this->assertTrue($model->supports(Capability::INPUT_TEXT));
    }

    public function testItSkipsEntriesWithoutIdentifier()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse(['object' => 'list', 'data' => [['object' => 'model', 'owned_by' => 'openai']]]),
        ]);

        $catalog = new ModelApiCatalog($httpClient, 'test-api-key');

        $this->assertSame([], $catalog->getModels());
    }

    public function testItHandlesAnEmptyResponse()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(['object' => 'list'])]);

        $catalog = new ModelApiCatalog($httpClient, 'test-api-key');

        $this->assertSame([], $catalog->getModels());
    }

    public function testItThrowsOnUnknownModel()
    {
        $catalog = $this->createCatalog();

        $this->expectException(ModelNotFoundException::class);

        $catalog->getModel('openai/does-not-exist');
    }

    public function testItSupportsACustomBaseUrl()
    {
        $callback = function (string $method, string $url): HttpResponse {
            $this->assertSame('https://api.orq.ai/v2/router/models', $url);

            return new JsonMockResponse($this->getModelsResponse());
        };

        $catalog = new ModelApiCatalog(new MockHttpClient([$callback]), 'test-api-key', 'https://api.orq.ai/v2/router/');

        $this->assertArrayHasKey('openai/gpt-4o', $catalog->getModels());
    }

    public function testItThrowsAPlatformExceptionOnRateLimit()
    {
        $catalog = $this->createFailingCatalog(429, 'Daily rate limit exceeded. Try again in 6 hour(s).');

        $this->expectException(RateLimitExceededException::class);
        $this->expectExceptionMessage('Daily rate limit exceeded. Try again in 6 hour(s).');

        $catalog->getModels();
    }

    public function testItThrowsAPlatformExceptionOnInvalidCredentials()
    {
        $catalog = $this->createFailingCatalog(401, 'Authorization header is required.');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authorization header is required.');

        $catalog->getModels();
    }

    public function testItThrowsAServerExceptionOnServerErrors()
    {
        $catalog = $this->createFailingCatalog(503, 'The upstream AI provider is overloaded.');

        try {
            $catalog->getModels();
            $this->fail(\sprintf('Expected a "%s" to be thrown.', ServerException::class));
        } catch (ServerException $e) {
            $this->assertSame('Server error (HTTP 503). The upstream AI provider is overloaded.', $e->getMessage());
            $this->assertSame(503, $e->getStatusCode());
        }
    }

    public function testItThrowsAPlatformExceptionOnAnyOtherError()
    {
        $catalog = $this->createFailingCatalog(403, 'The API key does not grant access to this workspace.');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to list the models of the Orq.ai router (HTTP 403): "The API key does not grant access to this workspace.".');

        $catalog->getModels();
    }

    public function testItReportsAnUnparseableErrorBody()
    {
        $httpClient = new MockHttpClient(new MockResponse('<html>gateway down</html>', ['http_code' => 403]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to list the models of the Orq.ai router (HTTP 403): "Unknown error.".');

        (new ModelApiCatalog($httpClient, 'test-api-key'))->getModels();
    }

    public function testItRetriesTheListingAfterATransientFailure()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse(['error' => ['message' => 'Daily rate limit exceeded.']], ['http_code' => 429]),
            new JsonMockResponse($this->getModelsResponse()),
        ]);

        $catalog = new ModelApiCatalog($httpClient, 'test-api-key');

        try {
            $catalog->getModels();
        } catch (RateLimitExceededException) {
            // The first listing fails, the catalog must not treat itself as loaded.
        }

        $this->assertArrayHasKey('openai/gpt-4o', $catalog->getModels());
    }

    private function createFailingCatalog(int $statusCode, string $message): ModelApiCatalog
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(
            ['error' => ['message' => $message, 'type' => 'error', 'param' => null, 'code' => null]],
            ['http_code' => $statusCode],
        ));

        return new ModelApiCatalog($httpClient, 'test-api-key');
    }

    private function createCatalog(): ModelApiCatalog
    {
        return new ModelApiCatalog(new MockHttpClient([new JsonMockResponse($this->getModelsResponse())]), 'test-api-key');
    }

    /**
     * @return array{object: string, data: list<array{id: string, object: string, owned_by: string, created: int}>}
     */
    private function getModelsResponse(): array
    {
        return [
            'object' => 'list',
            'data' => [
                [
                    'id' => 'openai/gpt-4o',
                    'object' => 'model',
                    'owned_by' => 'openai',
                    'created' => 1704067200,
                ],
                [
                    'id' => 'anthropic/claude-sonnet-4-5',
                    'object' => 'model',
                    'owned_by' => 'anthropic',
                    'created' => 1704067200,
                ],
                [
                    'id' => 'openai/text-embedding-3-small',
                    'object' => 'model',
                    'owned_by' => 'openai',
                    'created' => 1704067200,
                ],
            ],
        ];
    }
}
