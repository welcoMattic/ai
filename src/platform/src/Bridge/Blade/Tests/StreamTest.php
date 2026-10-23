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
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\Stream\Delta\TextDelta;
use Symfony\AI\Platform\Result\StreamResult;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Blade interleaves `: keepalive` comment lines and reports an interrupted stream with an in-band error chunk, which must surface as an exception.
 *
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class StreamTest extends TestCase
{
    public function testCompleteStreamWithKeepaliveIsConverted()
    {
        $body = <<<'SSE'
            : keepalive

            data: {"choices":[{"index":0,"delta":{"role":"assistant","content":"Hello"},"finish_reason":null}]}

            : keepalive

            data: {"choices":[{"index":0,"delta":{"content":" world"},"finish_reason":"stop"}]}

            data: [DONE]


            SSE;

        $text = '';
        foreach ($this->stream($body) as $delta) {
            if ($delta instanceof TextDelta) {
                $text .= $delta->getText();
            }
        }

        $this->assertSame('Hello world', $text);
    }

    public function testInBandStreamErrorRaisesAnExceptionInsteadOfEndingSilently()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream error: "upstream connection lost".');

        iterator_to_array($this->stream($this->interruptedBody()));
    }

    private function interruptedBody(): string
    {
        return <<<'SSE'
            : keepalive

            data: {"choices":[{"index":0,"delta":{"role":"assistant","content":"Hel"},"finish_reason":null}]}

            : keepalive

            data: {"choices":[],"error":{"message":"upstream connection lost","type":"api_error","code":"incomplete_stream"}}


            SSE;
    }

    /**
     * @return iterable<mixed>
     */
    private function stream(string $body): iterable
    {
        $httpClient = new MockHttpClient(new MockResponse($body, [
            'response_headers' => ['content-type' => 'text/event-stream'],
        ]));

        $platform = Factory::createPlatform('blade-test-api-key', $httpClient);
        $result = $platform->invoke('qwen3.6-35b-a3b', new MessageBag(Message::ofUser('Hello')), ['stream' => true])->getResult();

        $this->assertInstanceOf(StreamResult::class, $result);

        return $result->getContent();
    }
}
