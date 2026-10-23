<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\Tests\SpeechToText;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Blade\SpeechToText;
use Symfony\AI\Platform\Bridge\Blade\SpeechToText\ModelClient;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Exception\InvalidArgumentException;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ModelClientTest extends TestCase
{
    public function testItSupportsTheSpeechToTextModelOnly()
    {
        $client = new ModelClient(new MockHttpClient(), 'https://api.models.blade.sh', 'test-key');

        $this->assertTrue($client->supports(new SpeechToText('whisper-large-v3-turbo')));
        $this->assertFalse($client->supports(new CompletionsModel('voxtral-mini-3b')));
    }

    public function testItPostsTheAudioAsMultipartWithTheOptions()
    {
        $file = fopen('php://memory', 'w+');
        fwrite($file, 'fake-audio-bytes');
        rewind($file);

        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): JsonMockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://api.models.blade.sh/v1/audio/transcriptions', $url);
            self::assertContains('Authorization: Bearer test-key', $options['headers']);

            $body = $options['body'];
            if ($body instanceof \Closure) {
                $chunks = '';
                while ('' !== $chunk = $body(8192)) {
                    $chunks .= $chunk;
                }
                $body = $chunks;
            }

            self::assertStringContainsString('name="model"'."\r\n\r\n".'whisper-large-v3-turbo', $body);
            self::assertStringContainsString('name="language"'."\r\n\r\n".'en', $body);
            self::assertStringContainsString('name="file"', $body);
            self::assertStringContainsString('fake-audio-bytes', $body);

            return new JsonMockResponse(['text' => 'Hello']);
        });

        $client = new ModelClient($httpClient, 'https://api.models.blade.sh/', 'test-key');
        $result = $client->request(new SpeechToText('whisper-large-v3-turbo'), ['file' => $file], ['language' => 'en']);

        $this->assertInstanceOf(RawHttpResult::class, $result);
        $this->assertSame(['text' => 'Hello'], $result->getData());
        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testItRejectsAStringPayload()
    {
        $this->expectException(InvalidArgumentException::class);

        (new ModelClient(new MockHttpClient(), 'https://api.models.blade.sh', 'test-key'))->request(new SpeechToText('whisper-large-v3-turbo'), 'audio');
    }

    public function testItRequiresTheFilePart()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The payload must contain a "file" part');

        (new ModelClient(new MockHttpClient(), 'https://api.models.blade.sh', 'test-key'))->request(new SpeechToText('whisper-large-v3-turbo'), ['audio' => 'bytes']);
    }
}
