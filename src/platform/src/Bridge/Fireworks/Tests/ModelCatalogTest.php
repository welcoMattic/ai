<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Fireworks\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Fireworks\Fireworks;
use Symfony\AI\Platform\Bridge\Fireworks\ModelCatalog;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Exception\ModelNotFoundException;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class ModelCatalogTest extends TestCase
{
    public function testGetModelResolvesThroughTheGetModelRoute()
    {
        $requestedUrl = null;
        $httpClient = new MockHttpClient(static function (string $method, string $url) use (&$requestedUrl): JsonMockResponse {
            $requestedUrl = $url;

            self::assertSame('GET', $method);

            return new JsonMockResponse(['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL', 'supportsTools' => true]);
        });

        $catalog = new ModelCatalog($httpClient, 'api-key');
        $model = $catalog->getModel('accounts/fireworks/models/kimi-k2p6');

        // Resolving via the listing would transfer the whole account, megabytes per process.
        $this->assertSame('https://api.fireworks.ai/v1/accounts/fireworks/models/kimi-k2p6', $requestedUrl);
        $this->assertInstanceOf(Fireworks::class, $model);
        $this->assertSame('accounts/fireworks/models/kimi-k2p6', $model->getName());
        $this->assertContains(Capability::INPUT_MESSAGES, $model->getCapabilities());
        $this->assertContains(Capability::OUTPUT_TEXT, $model->getCapabilities());
        $this->assertContains(Capability::OUTPUT_STREAMING, $model->getCapabilities());
        $this->assertContains(Capability::OUTPUT_STRUCTURED, $model->getCapabilities());
        $this->assertContains(Capability::TOOL_CALLING, $model->getCapabilities());
        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testGetModelAcceptsTheShortModelId()
    {
        $requestedUrl = null;
        $httpClient = new MockHttpClient(static function (string $method, string $url) use (&$requestedUrl): JsonMockResponse {
            $requestedUrl = $url;

            return new JsonMockResponse(['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL']);
        });

        $model = (new ModelCatalog($httpClient, 'api-key'))->getModel('kimi-k2p6');

        $this->assertSame('https://api.fireworks.ai/v1/accounts/fireworks/models/kimi-k2p6', $requestedUrl);
        $this->assertSame('accounts/fireworks/models/kimi-k2p6', $model->getName());
    }

    public function testGetModelKeepsOptionsWhenExpandingTheShortModelId()
    {
        $catalog = $this->catalogFor(['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL']);
        $model = $catalog->getModel('kimi-k2p6?temperature=0.5');

        $this->assertSame('accounts/fireworks/models/kimi-k2p6', $model->getName());
        $this->assertSame(['temperature' => 0.5], $model->getOptions());
    }

    public function testGetModelExpandsTheShortModelIdAgainstTheConfiguredAccount()
    {
        $requestedUrl = null;
        $httpClient = new MockHttpClient(static function (string $method, string $url) use (&$requestedUrl): JsonMockResponse {
            $requestedUrl = $url;

            return new JsonMockResponse(['name' => 'accounts/acme/models/my-tune', 'kind' => 'HF_BASE_MODEL']);
        });

        $model = (new ModelCatalog($httpClient, 'api-key', 'acme'))->getModel('my-tune');

        $this->assertSame('https://api.fireworks.ai/v1/accounts/acme/models/my-tune', $requestedUrl);
        $this->assertSame('accounts/acme/models/my-tune', $model->getName());
    }

    public function testGetModelDetectsEmbeddingModel()
    {
        $catalog = $this->catalogFor(['name' => 'accounts/fireworks/models/qwen3-embedding-8b', 'kind' => 'EMBEDDING_MODEL']);
        $model = $catalog->getModel('accounts/fireworks/models/qwen3-embedding-8b');

        $this->assertContains(Capability::INPUT_TEXT, $model->getCapabilities());
        $this->assertContains(Capability::EMBEDDINGS, $model->getCapabilities());
        $this->assertNotContains(Capability::OUTPUT_STRUCTURED, $model->getCapabilities());
    }

    public function testGetModelDetectsRerankModelFiledUnderTheEmbeddingKind()
    {
        $catalog = $this->catalogFor(['name' => 'accounts/fireworks/models/qwen3-reranker-8b', 'kind' => 'EMBEDDING_MODEL']);
        $model = $catalog->getModel('accounts/fireworks/models/qwen3-reranker-8b');

        $this->assertContains(Capability::RERANKING, $model->getCapabilities());
        $this->assertNotContains(Capability::EMBEDDINGS, $model->getCapabilities());
    }

    public function testGetModelDetectsVisionModel()
    {
        $catalog = $this->catalogFor(['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL', 'supportsImageInput' => true]);
        $model = $catalog->getModel('accounts/fireworks/models/kimi-k2p6');

        $this->assertContains(Capability::INPUT_IMAGE, $model->getCapabilities());
    }

    public function testGetModelDetectsImageModel()
    {
        $catalog = $this->catalogFor(['name' => 'accounts/fireworks/models/flux-1-schnell-fp8', 'kind' => 'FLUMINA_BASE_MODEL']);
        $model = $catalog->getModel('accounts/fireworks/models/flux-1-schnell-fp8');

        $this->assertContains(Capability::TEXT_TO_IMAGE, $model->getCapabilities());
        $this->assertContains(Capability::OUTPUT_IMAGE, $model->getCapabilities());
        $this->assertNotContains(Capability::OUTPUT_TEXT, $model->getCapabilities());
    }

    public function testGetModelTreatsCustomModelsAsChatModels()
    {
        $catalog = $this->catalogFor(['name' => 'accounts/fireworks/models/qwen3p7-max', 'kind' => 'CUSTOM_MODEL', 'supportsTools' => true]);
        $model = $catalog->getModel('accounts/fireworks/models/qwen3p7-max');

        $this->assertContains(Capability::INPUT_MESSAGES, $model->getCapabilities());
        $this->assertContains(Capability::TOOL_CALLING, $model->getCapabilities());
    }

    public function testGetModelThrowsWhenTheGatewayDoesNotKnowTheModel()
    {
        $catalog = new ModelCatalog(new MockHttpClient(new JsonMockResponse(['error' => 'nope'], ['http_code' => 404])), 'api-key');

        $this->expectException(ModelNotFoundException::class);

        $catalog->getModel('accounts/fireworks/models/does-not-exist');
    }

    public function testGetModelThrowsForANonInvocableAddon()
    {
        $catalog = $this->catalogFor(['name' => 'accounts/fireworks/models/eagle1-kimi-k2-instruct-0905-v0', 'kind' => 'DRAFT_ADDON']);

        $this->expectException(ModelNotFoundException::class);

        $catalog->getModel('accounts/fireworks/models/eagle1-kimi-k2-instruct-0905-v0');
    }

    public function testGetModelIsOnlyFetchedOnce()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse(['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL']),
        ]);

        $catalog = new ModelCatalog($httpClient, 'api-key');
        $catalog->getModel('accounts/fireworks/models/kimi-k2p6');
        $catalog->getModel('accounts/fireworks/models/kimi-k2p6');

        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testGetModelDoesNotRefetchWhatTheListingAlreadyLoaded()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse(['models' => [['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL', 'supportsServerless' => true]]]),
        ]);

        $catalog = new ModelCatalog($httpClient, 'api-key');
        $catalog->getModels();
        $catalog->getModel('accounts/fireworks/models/kimi-k2p6');

        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testGetModelsPaginates()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'models' => [['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL', 'supportsServerless' => true]],
                'nextPageToken' => 'page2',
            ]),
            new JsonMockResponse([
                'models' => [['name' => 'accounts/fireworks/models/gpt-oss-120b', 'kind' => 'HF_BASE_MODEL', 'supportsTools' => true, 'supportsServerless' => true]],
            ]),
        ]);

        $catalog = new ModelCatalog($httpClient, 'api-key');
        $models = $catalog->getModels();

        $this->assertArrayHasKey('accounts/fireworks/models/kimi-k2p6', $models);
        $this->assertArrayHasKey('accounts/fireworks/models/gpt-oss-120b', $models);
        $this->assertSame(2, $httpClient->getRequestsCount());
    }

    public function testGetModelsAreOnlyLoadedOnce()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse(['models' => [['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL', 'supportsServerless' => true]]]),
        ]);

        $catalog = new ModelCatalog($httpClient, 'api-key');
        $catalog->getModels();
        $catalog->getModels();

        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testGetModelsSkipsNonInvocableAddons()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'models' => [
                    ['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL', 'supportsServerless' => true],
                    ['name' => 'accounts/fireworks/models/eagle1-kimi-k2-instruct-0905-v0', 'kind' => 'DRAFT_ADDON', 'supportsServerless' => true],
                    ['name' => 'accounts/fireworks/models/flux-1-dev-controlnet-union', 'kind' => 'FLUMINA_ADDON', 'supportsServerless' => true],
                ],
            ]),
        ]);

        $catalog = new ModelCatalog($httpClient, 'api-key');
        $models = $catalog->getModels();

        $this->assertArrayHasKey('accounts/fireworks/models/kimi-k2p6', $models);
        $this->assertArrayNotHasKey('accounts/fireworks/models/eagle1-kimi-k2-instruct-0905-v0', $models);
        $this->assertArrayNotHasKey('accounts/fireworks/models/flux-1-dev-controlnet-union', $models);
    }

    public function testGetModelsOnlyListsServerlessModels()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'models' => [
                    ['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL', 'supportsServerless' => true],
                    ['name' => 'accounts/fireworks/models/flux-1-dev-fp8', 'kind' => 'FLUMINA_BASE_MODEL', 'supportsServerless' => false],
                ],
            ]),
        ]);

        $catalog = new ModelCatalog($httpClient, 'api-key');
        $models = $catalog->getModels();

        $this->assertArrayHasKey('accounts/fireworks/models/kimi-k2p6', $models);
        $this->assertArrayNotHasKey('accounts/fireworks/models/flux-1-dev-fp8', $models);
    }

    public function testGetModelStillResolvesAModelTheListingLeavesOut()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'models' => [['name' => 'accounts/fireworks/models/kimi-k2p6', 'kind' => 'HF_BASE_MODEL', 'supportsServerless' => true]],
            ]),
            new JsonMockResponse(['name' => 'accounts/fireworks/models/flux-1-dev-fp8', 'kind' => 'FLUMINA_BASE_MODEL', 'supportsServerless' => false]),
        ]);

        $catalog = new ModelCatalog($httpClient, 'api-key');
        $catalog->getModels();

        $model = $catalog->getModel('accounts/fireworks/models/flux-1-dev-fp8');

        $this->assertContains(Capability::TEXT_TO_IMAGE, $model->getCapabilities());
        $this->assertSame(2, $httpClient->getRequestsCount());
    }

    public function testItThrowsAPlatformExceptionWhenTheGatewayFails()
    {
        $catalog = new ModelCatalog(new MockHttpClient(new JsonMockResponse(['error' => 'nope'], ['http_code' => 401])), 'api-key');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot retrieve models from the Fireworks gateway');

        $catalog->getModels();
    }

    /**
     * @param array<string, mixed> $model
     */
    private function catalogFor(array $model): ModelCatalog
    {
        return new ModelCatalog(new MockHttpClient(new JsonMockResponse($model)), 'api-key');
    }
}
