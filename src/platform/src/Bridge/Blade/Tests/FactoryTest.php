<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Blade\Factory;
use Symfony\AI\Platform\Message\Content\Audio;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Result\BinaryResult;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\Result\VectorResult;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class FactoryTest extends TestCase
{
    public function testItCreatesPlatformWithDefaultSettings()
    {
        $platform = Factory::createPlatform('blade-test-api-key');

        $this->assertInstanceOf(Platform::class, $platform);
    }

    public function testItCreatesPlatformWithCustomHttpClient()
    {
        $httpClient = new MockHttpClient();
        $platform = Factory::createPlatform('blade-test-api-key', $httpClient);

        $this->assertInstanceOf(Platform::class, $platform);
    }

    public function testItCreatesPlatformWithEventSourceHttpClient()
    {
        $httpClient = new EventSourceHttpClient(new MockHttpClient());
        $platform = Factory::createPlatform('blade-test-api-key', $httpClient);

        $this->assertInstanceOf(Platform::class, $platform);
    }

    public function testItInvokesEmbeddings()
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url): JsonMockResponse {
            self::assertSame('https://api.models.blade.sh/v1/embeddings', $url);

            return new JsonMockResponse(['data' => [['embedding' => [0.1, 0.2, 0.3]]]]);
        });

        $result = Factory::createPlatform('blade-test-api-key', $httpClient)->invoke('qwen3-embedding-8b', 'Hello')->getResult();

        $this->assertInstanceOf(VectorResult::class, $result);
    }

    public function testItInvokesSpeechToText()
    {
        $file = tempnam(sys_get_temp_dir(), 'blade-audio');
        file_put_contents($file, 'fake-audio-bytes');

        try {
            $httpClient = new MockHttpClient(static function (string $method, string $url): JsonMockResponse {
                self::assertSame('https://api.models.blade.sh/v1/audio/transcriptions', $url);

                return new JsonMockResponse(['text' => 'Hello', 'usage' => ['type' => 'duration', 'seconds' => 3]]);
            });

            $result = Factory::createPlatform('blade-test-api-key', $httpClient)->invoke('whisper-large-v3-turbo', Audio::fromFile($file))->getResult();
        } finally {
            unlink($file);
        }

        $this->assertInstanceOf(TextResult::class, $result);
        $this->assertSame('Hello', $result->getContent());
        $this->assertSame(['type' => 'duration', 'seconds' => 3], $result->getMetadata()->get('usage'));
    }

    public function testItInvokesTextToSpeech()
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url): MockResponse {
            self::assertSame('https://api.models.blade.sh/v1/audio/speech', $url);

            return new MockResponse('RIFF', ['response_headers' => ['content-type' => 'audio/wav']]);
        });

        $result = Factory::createPlatform('blade-test-api-key', $httpClient)->invoke('kokoro-82m', 'Hello', ['voice' => 'af_heart'])->getResult();

        $this->assertInstanceOf(BinaryResult::class, $result);
        $this->assertSame('audio/wav', $result->getMimeType());
    }

    public function testItInvokesImageGeneration()
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url): JsonMockResponse {
            self::assertSame('https://api.models.blade.sh/v1/images/generations', $url);

            return new JsonMockResponse(['created' => 1792744675, 'data' => [['b64_json' => base64_encode('png-bytes')]]]);
        });

        $result = Factory::createPlatform('blade-test-api-key', $httpClient)->invoke('flux-2-klein', 'A red square')->getResult();

        $this->assertInstanceOf(BinaryResult::class, $result);
        $this->assertSame('png-bytes', $result->getContent());
    }
}
