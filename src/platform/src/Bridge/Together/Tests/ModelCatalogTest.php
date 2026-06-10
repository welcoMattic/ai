<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Together\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Together\ModelCatalog;
use Symfony\AI\Platform\Bridge\Together\Together;
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
    private const MODELS_FIXTURE = [
        ['id' => 'openai/gpt-oss-120b', 'object' => 'model', 'type' => 'chat'],
        ['id' => 'meta-llama/Llama-Guard-4-12B', 'object' => 'model', 'type' => 'moderation'],
        ['id' => 'black-forest-labs/FLUX.2-flex', 'object' => 'model', 'type' => 'image'],
        ['id' => 'BAAI/bge-base-en-v1.5', 'object' => 'model', 'type' => 'embedding'],
        ['id' => 'Salesforce/Llama-Rank-V1', 'object' => 'model', 'type' => 'rerank'],
        ['id' => 'mistralai/Mixtral-8x7B-v0.1', 'object' => 'model', 'type' => 'language'],
        ['id' => 'cartesia/sonic', 'object' => 'model', 'type' => 'audio'],
        ['id' => 'openai/whisper-large-v3', 'object' => 'model', 'type' => 'transcribe'],
        ['id' => 'openai/sora-2', 'object' => 'model', 'type' => 'video'],
    ];

    public function testItRetrievesModelsFromTheApi()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $models = $catalog->getModels();

        $this->assertCount(9, $models);
        $this->assertArrayHasKey('openai/gpt-oss-120b', $models);
        $this->assertSame(Together::class, $models['openai/gpt-oss-120b']['class']);
        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testItMapsChatTypeToCompletionCapabilities()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $model = $catalog->getModel('openai/gpt-oss-120b');

        $this->assertInstanceOf(Together::class, $model);
        $this->assertSame('openai/gpt-oss-120b', $model->getName());
        $this->assertSame([
            Capability::INPUT_MESSAGES,
            Capability::OUTPUT_TEXT,
            Capability::OUTPUT_STREAMING,
            Capability::OUTPUT_STRUCTURED,
            Capability::TOOL_CALLING,
        ], $model->getCapabilities());
    }

    public function testItMapsEmbeddingTypeToEmbeddingsCapabilities()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $model = $catalog->getModel('BAAI/bge-base-en-v1.5');

        $this->assertSame([Capability::INPUT_TEXT, Capability::EMBEDDINGS], $model->getCapabilities());
    }

    public function testItMapsImageTypeToImageCapabilities()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $model = $catalog->getModel('black-forest-labs/FLUX.2-flex');

        $this->assertSame([Capability::INPUT_TEXT, Capability::OUTPUT_IMAGE, Capability::TEXT_TO_IMAGE], $model->getCapabilities());
    }

    public function testItMapsRerankTypeToRerankingCapabilities()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $model = $catalog->getModel('Salesforce/Llama-Rank-V1');

        $this->assertSame([Capability::INPUT_TEXT, Capability::RERANKING], $model->getCapabilities());
    }

    public function testItMapsLanguageTypeToBaseCompletionCapabilities()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $model = $catalog->getModel('mistralai/Mixtral-8x7B-v0.1');

        $this->assertSame([
            Capability::INPUT_MESSAGES,
            Capability::OUTPUT_TEXT,
            Capability::OUTPUT_STREAMING,
        ], $model->getCapabilities());
    }

    public function testItMapsAudioTypeToTextToSpeechCapabilities()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $model = $catalog->getModel('cartesia/sonic');

        $this->assertSame([
            Capability::INPUT_TEXT,
            Capability::TEXT_TO_SPEECH,
            Capability::OUTPUT_AUDIO,
        ], $model->getCapabilities());
    }

    public function testItMapsTranscribeTypeToSpeechToTextCapabilities()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $model = $catalog->getModel('openai/whisper-large-v3');

        $this->assertSame([
            Capability::INPUT_AUDIO,
            Capability::SPEECH_TO_TEXT,
            Capability::OUTPUT_TEXT,
        ], $model->getCapabilities());
    }

    public function testItLeavesTypesWithoutAnEndpointTextOnly()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $model = $catalog->getModel('openai/sora-2');

        $this->assertSame([Capability::INPUT_TEXT], $model->getCapabilities());
    }

    public function testItParsesOptionsFromTheModelName()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $model = $catalog->getModel('openai/gpt-oss-120b?temperature=0.5');

        $this->assertSame('openai/gpt-oss-120b', $model->getName());
        $this->assertSame(['temperature' => 0.5], $model->getOptions());
    }

    public function testItThrowsWhenModelIsUnknown()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Model "unknown/model" not found in "Symfony\\AI\\Platform\\Bridge\\Together\\ModelCatalog".');

        $catalog->getModel('unknown/model');
    }

    public function testItThrowsWhenTheApiReturnsAnError()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse([], ['http_code' => 500])], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot retrieve models from the Together API (Status code: 500).');

        $catalog->getModels();
    }

    public function testItIgnoresMalformedEntries()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse([
            ['id' => 'valid/model', 'type' => 'chat'],
            ['object' => 'model'],
            'not-an-array',
            ['id' => 123, 'type' => 'chat'],
        ])], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $models = $catalog->getModels();

        $this->assertArrayHasKey('valid/model', $models);
        $this->assertCount(1, $models);
    }

    public function testItMemoizesTheApiCall()
    {
        $httpClient = new MockHttpClient([new JsonMockResponse(self::MODELS_FIXTURE)], 'https://api.together.xyz');
        $catalog = new ModelCatalog($httpClient);

        $catalog->getModels();
        $catalog->getModel('openai/gpt-oss-120b');
        $catalog->getModels();

        $this->assertSame(1, $httpClient->getRequestsCount());
    }
}
