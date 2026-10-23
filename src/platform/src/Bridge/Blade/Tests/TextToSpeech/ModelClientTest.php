<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\Tests\TextToSpeech;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Blade\TextToSpeech;
use Symfony\AI\Platform\Bridge\Blade\TextToSpeech\ModelClient;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Exception\InvalidArgumentException;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ModelClientTest extends TestCase
{
    public function testItSupportsTheTextToSpeechModelOnly()
    {
        $client = new ModelClient(new MockHttpClient(), 'https://api.models.blade.sh', 'test-key');

        $this->assertTrue($client->supports(new TextToSpeech('kokoro-82m')));
        $this->assertFalse($client->supports(new CompletionsModel('voxtral-mini-3b')));
    }

    public function testItPostsTheInputWithTheVoice()
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://api.models.blade.sh/v1/audio/speech', $url);
            self::assertContains('Authorization: Bearer test-key', $options['headers']);
            self::assertSame(['voice' => 'af_heart', 'model' => 'kokoro-82m', 'input' => 'Hello'], json_decode($options['body'], true));

            return new MockResponse('RIFF', ['response_headers' => ['content-type' => 'audio/wav']]);
        });

        $client = new ModelClient($httpClient, 'https://api.models.blade.sh/', 'test-key');
        $result = $client->request(new TextToSpeech('kokoro-82m'), 'Hello', ['voice' => 'af_heart']);

        $this->assertInstanceOf(RawHttpResult::class, $result);
        $this->assertSame('RIFF', $result->getObject()->getContent());
    }

    public function testItDoesNotForceAVoice()
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
            self::assertSame(['model' => 'kokoro-82m', 'input' => 'Bonjour'], json_decode($options['body'], true));

            return new MockResponse('RIFF');
        });

        (new ModelClient($httpClient, 'https://api.models.blade.sh', 'test-key'))->request(new TextToSpeech('kokoro-82m'), ['text' => 'Bonjour']);

        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testItRejectsStreaming()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Streaming text to speech results is not supported yet.');

        (new ModelClient(new MockHttpClient(), 'https://api.models.blade.sh', 'test-key'))->request(new TextToSpeech('kokoro-82m'), 'Hello', ['stream' => true]);
    }

    public function testItRequiresTheTextInAnArrayPayload()
    {
        $this->expectException(InvalidArgumentException::class);

        (new ModelClient(new MockHttpClient(), 'https://api.models.blade.sh', 'test-key'))->request(new TextToSpeech('kokoro-82m'), ['input' => 'Hello']);
    }
}
