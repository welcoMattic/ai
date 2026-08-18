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
use Symfony\AI\Platform\Bridge\Generic\FallbackModelCatalog;
use Symfony\AI\Platform\Bridge\OrqAi\Factory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Platform;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface as HttpResponse;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class FactoryTest extends TestCase
{
    public function testItCreatesPlatformWithDefaultSettings()
    {
        $platform = Factory::createPlatform('test-api-key');

        $this->assertInstanceOf(Platform::class, $platform);
    }

    public function testItCreatesPlatformWithCustomHttpClient()
    {
        $platform = Factory::createPlatform('test-api-key', new MockHttpClient());

        $this->assertInstanceOf(Platform::class, $platform);
    }

    public function testItCreatesPlatformWithEventSourceHttpClient()
    {
        $platform = Factory::createPlatform('test-api-key', new EventSourceHttpClient(new MockHttpClient()));

        $this->assertInstanceOf(Platform::class, $platform);
    }

    public function testItCallsTheRouterCompletionsEndpoint()
    {
        $callback = function (string $method, string $url, array $options): HttpResponse {
            $this->assertSame('POST', $method);
            $this->assertSame('https://api.orq.ai/v3/router/chat/completions', $url);
            $this->assertSame('Authorization: Bearer test-api-key', $options['normalized_headers']['authorization'][0]);
            $this->assertStringContainsString('"model":"openai\/gpt-4o"', $options['body']);

            return new JsonMockResponse([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Ahoy!'], 'finish_reason' => 'stop'],
                ],
            ]);
        };

        $platform = Factory::createPlatform('test-api-key', new MockHttpClient([$callback]));
        $result = $platform->invoke('openai/gpt-4o', new MessageBag(Message::ofUser('Hello')));

        $this->assertSame('Ahoy!', $result->asText());
    }

    public function testItCallsTheRouterEmbeddingsEndpoint()
    {
        $callback = function (string $method, string $url, array $options): HttpResponse {
            $this->assertSame('POST', $method);
            $this->assertSame('https://api.orq.ai/v3/router/embeddings', $url);
            $this->assertSame('Authorization: Bearer test-api-key', $options['normalized_headers']['authorization'][0]);

            return new JsonMockResponse([
                'data' => [['index' => 0, 'embedding' => [0.1, 0.2, 0.3]]],
            ]);
        };

        $platform = Factory::createPlatform('test-api-key', new MockHttpClient([$callback]));
        $result = $platform->invoke('openai/text-embedding-3-small', 'Hello');

        $this->assertCount(1, $result->asVectors());
    }

    public function testItSupportsACustomBaseUrl()
    {
        $callback = function (string $method, string $url): HttpResponse {
            $this->assertSame('https://api.orq.ai/v2/router/chat/completions', $url);

            return new JsonMockResponse([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Ahoy!'], 'finish_reason' => 'stop'],
                ],
            ]);
        };

        $platform = Factory::createPlatform(
            'test-api-key',
            new MockHttpClient([$callback]),
            baseUrl: 'https://api.orq.ai/v2/router',
        );
        $result = $platform->invoke('openai/gpt-4o', new MessageBag(Message::ofUser('Hello')));

        $this->assertSame('Ahoy!', $result->asText());
    }

    public function testItForwardsGatewayOptions()
    {
        $callback = function (string $method, string $url, array $options): HttpResponse {
            $this->assertStringContainsString('"retry":{"count":3,"on_codes":[429,500]}', $options['body']);
            $this->assertStringContainsString('"cache":{"type":"exact_match","ttl":3600}', $options['body']);

            return new JsonMockResponse([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Ahoy!'], 'finish_reason' => 'stop'],
                ],
            ]);
        };

        $platform = Factory::createPlatform('test-api-key', new MockHttpClient([$callback]));
        $result = $platform->invoke('openai/gpt-4o', new MessageBag(Message::ofUser('Hello')), [
            'retry' => ['count' => 3, 'on_codes' => [429, 500]],
            'cache' => ['type' => 'exact_match', 'ttl' => 3600],
        ]);

        $this->assertSame('Ahoy!', $result->asText());
    }

    public function testTheDefaultCatalogCreatesCompletionsModel()
    {
        $this->assertInstanceOf(CompletionsModel::class, (new FallbackModelCatalog())->getModel('openai/gpt-4o'));
    }

    public function testTheDefaultCatalogCreatesEmbeddingsModel()
    {
        $this->assertInstanceOf(EmbeddingsModel::class, (new FallbackModelCatalog())->getModel('openai/text-embedding-3-small'));
    }
}
