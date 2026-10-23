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
use Symfony\AI\Platform\Bridge\Blade\SpeechToText\ResultConverter;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Exception\AuthenticationException;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ResultConverterTest extends TestCase
{
    public function testItSupportsTheSpeechToTextModelOnly()
    {
        $converter = new ResultConverter();

        $this->assertTrue($converter->supports(new SpeechToText('whisper-large-v3-turbo')));
        $this->assertFalse($converter->supports(new CompletionsModel('voxtral-mini-3b')));
    }

    public function testItConvertsATranscriptionWithItsUsage()
    {
        $result = $this->convert(new JsonMockResponse([
            'text' => ' Wishing you a Merry Christmas and a Happy New Year.',
            'usage' => ['type' => 'duration', 'seconds' => 3],
        ]));

        $this->assertInstanceOf(TextResult::class, $result);
        $this->assertSame(' Wishing you a Merry Christmas and a Happy New Year.', $result->getContent());
        $this->assertSame(['type' => 'duration', 'seconds' => 3], $result->getMetadata()->get('usage'));
    }

    public function testItKeepsTheVerboseFieldsAsMetadata()
    {
        $segments = [['id' => 0, 'start' => 0.0, 'end' => 2.48, 'text' => ' Wishing you a Merry Christmas.']];

        $result = $this->convert(new JsonMockResponse([
            'duration' => 2.594125,
            'language' => 'en',
            'text' => ' Wishing you a Merry Christmas.',
            'segments' => $segments,
            'words' => null,
        ]));

        $this->assertSame(2.594125, $result->getMetadata()->get('duration'));
        $this->assertSame('en', $result->getMetadata()->get('language'));
        $this->assertSame($segments, $result->getMetadata()->get('segments'));
        $this->assertFalse($result->getMetadata()->has('words'));
    }

    public function testItConvertsAPlainTextResponse()
    {
        $result = $this->convert(new MockResponse("Hello world\n", ['response_headers' => ['content-type' => 'text/plain']]));

        $this->assertInstanceOf(TextResult::class, $result);
        $this->assertSame("Hello world\n", $result->getContent());
    }

    public function testItFailsWithoutText()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The response is missing the required "text" field.');

        $this->convert(new JsonMockResponse(['duration' => 1.0]));
    }

    public function testItMapsKnownHttpErrors()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key.');

        $this->convert(new JsonMockResponse(['error' => ['message' => 'Invalid API key.', 'type' => 'authentication_error']], ['http_code' => 401]));
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

    private function convert(MockResponse $response): TextResult
    {
        $httpClient = new MockHttpClient($response);

        $result = (new ResultConverter())->convert(new RawHttpResult($httpClient->request('POST', 'https://api.models.blade.sh/v1/audio/transcriptions')));
        $this->assertInstanceOf(TextResult::class, $result);

        return $result;
    }
}
