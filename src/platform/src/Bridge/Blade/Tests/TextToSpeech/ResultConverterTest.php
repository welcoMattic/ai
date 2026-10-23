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
use Symfony\AI\Platform\Bridge\Blade\TextToSpeech\ResultConverter;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Exception\BadRequestException;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Result\BinaryResult;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ResultConverterTest extends TestCase
{
    public function testItSupportsTheTextToSpeechModelOnly()
    {
        $converter = new ResultConverter();

        $this->assertTrue($converter->supports(new TextToSpeech('kokoro-82m')));
        $this->assertFalse($converter->supports(new CompletionsModel('voxtral-mini-3b')));
    }

    public function testItConvertsTheAudioWithItsMimeType()
    {
        $result = $this->convert(new MockResponse('RIFF....WAVE', ['response_headers' => ['content-type' => 'audio/wav']]));

        $this->assertInstanceOf(BinaryResult::class, $result);
        $this->assertSame('RIFF....WAVE', $result->getContent());
        $this->assertSame('audio/wav', $result->getMimeType());
    }

    public function testItDropsTheContentTypeParameters()
    {
        $result = $this->convert(new MockResponse('RIFF', ['response_headers' => ['content-type' => 'audio/wav; charset=binary']]));

        $this->assertInstanceOf(BinaryResult::class, $result);
        $this->assertSame('audio/wav', $result->getMimeType());
    }

    public function testItMapsKnownHttpErrors()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Unsupported Kyutai voice.');

        $this->convert(new JsonMockResponse(['error' => ['message' => 'Unsupported Kyutai voice.', 'type' => 'invalid_request_error', 'param' => 'voice']], ['http_code' => 400]));
    }

    public function testItFailsOnOtherHttpErrors()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error "paid_credit_required"-insufficient_quota (-): "A paid wallet is required.".');

        $this->convert(new JsonMockResponse(['error' => ['message' => 'A paid wallet is required.', 'type' => 'insufficient_quota', 'code' => 'paid_credit_required']], ['http_code' => 402]));
    }

    public function testItFallsBackToTheRawBodyWhenTheErrorIsNotAnEnvelope()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected response code 402: "plain failure"');

        $this->convert(new MockResponse('plain failure', ['http_code' => 402]));
    }

    private function convert(MockResponse $response): mixed
    {
        $httpClient = new MockHttpClient($response);

        return (new ResultConverter())->convert(new RawHttpResult($httpClient->request('POST', 'https://api.models.blade.sh/v1/audio/speech')));
    }
}
