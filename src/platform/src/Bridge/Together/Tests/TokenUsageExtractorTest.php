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
use Symfony\AI\Platform\Bridge\Together\TokenUsageExtractor;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class TokenUsageExtractorTest extends TestCase
{
    public function testItExtractsTheUsageOfAJsonResponse()
    {
        $httpClient = new MockHttpClient(new JsonMockResponse([
            'model' => 'openai/gpt-oss-120b',
            'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 34, 'total_tokens' => 46],
        ]), 'https://api.together.xyz');

        $tokenUsage = (new TokenUsageExtractor())->extract(new RawHttpResult($httpClient->request('POST', '/v1/chat/completions')));

        $this->assertNotNull($tokenUsage);
        $this->assertSame(12, $tokenUsage->getPromptTokens());
        $this->assertSame(34, $tokenUsage->getCompletionTokens());
        $this->assertSame(46, $tokenUsage->getTotalTokens());
        $this->assertSame('openai/gpt-oss-120b', $tokenUsage->getModel());
    }

    public function testItSkipsTheRawAudioOfASpeechResponse()
    {
        $httpClient = new MockHttpClient(new MockResponse('not json, but audio bytes', [
            'response_headers' => ['content-type' => 'audio/mpeg'],
        ]), 'https://api.together.xyz');

        $tokenUsage = (new TokenUsageExtractor())->extract(new RawHttpResult($httpClient->request('POST', '/v1/audio/speech')));

        $this->assertNull($tokenUsage);
    }
}
