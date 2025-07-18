<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Anthropic;

use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Response\RawHttpResponse;
use Symfony\AI\Platform\Response\RawResponseInterface;
use Symfony\AI\Platform\Response\ResponseInterface;
use Symfony\AI\Platform\Response\StreamResponse;
use Symfony\AI\Platform\Response\TextResponse;
use Symfony\AI\Platform\Response\ToolCall;
use Symfony\AI\Platform\Response\ToolCallResponse;
use Symfony\AI\Platform\ResponseConverterInterface;
use Symfony\Component\HttpClient\Chunk\ServerSentEvent;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Contracts\HttpClient\ResponseInterface as HttpResponse;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
class ResponseConverter implements ResponseConverterInterface
{
    public function supports(Model $model): bool
    {
        return $model instanceof Claude;
    }

    public function convert(RawHttpResponse|RawResponseInterface $response, array $options = []): ResponseInterface
    {
        if ($options['stream'] ?? false) {
            return new StreamResponse($this->convertStream($response->getRawObject()));
        }

        $data = $response->getRawData();

        if (!isset($data['content']) || [] === $data['content']) {
            throw new RuntimeException('Response does not contain any content');
        }

        $toolCalls = [];
        foreach ($data['content'] as $content) {
            if ('tool_use' === $content['type']) {
                $toolCalls[] = new ToolCall($content['id'], $content['name'], $content['input']);
            }
        }

        if (!isset($data['content'][0]['text']) && [] === $toolCalls) {
            throw new RuntimeException('Response content does not contain any text nor tool calls.');
        }

        if ([] !== $toolCalls) {
            return new ToolCallResponse(...$toolCalls);
        }

        return new TextResponse($data['content'][0]['text']);
    }

    private function convertStream(HttpResponse $response): \Generator
    {
        foreach ((new EventSourceHttpClient())->stream($response) as $chunk) {
            if (!$chunk instanceof ServerSentEvent || '[DONE]' === $chunk->getData()) {
                continue;
            }

            try {
                $data = $chunk->getArrayData();
            } catch (JsonException) {
                // try catch only needed for Symfony 6.4
                continue;
            }

            if ('content_block_delta' != $data['type'] || !isset($data['delta']['text'])) {
                continue;
            }

            yield $data['delta']['text'];
        }
    }
}
