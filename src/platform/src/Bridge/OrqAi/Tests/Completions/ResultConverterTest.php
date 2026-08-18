<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\OrqAi\Tests\Completions;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\OrqAi\Completions\ResultConverter;
use Symfony\AI\Platform\FinishReason\FinishReason;
use Symfony\AI\Platform\FinishReason\FinishReasonCase;
use Symfony\AI\Platform\Result\InMemoryRawResult;
use Symfony\AI\Platform\Result\Stream\Delta\ToolCallComplete;
use Symfony\AI\Platform\Result\Stream\Delta\ToolCallStart;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\Result\ToolCallResult;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ResultConverterTest extends TestCase
{
    public function testItConvertsToolCallsReportedWithAStopFinishReason()
    {
        $result = (new ResultConverter())->convert(new InMemoryRawResult($this->toolCallData('stop'), [], $this->httpResponseStub()));

        $this->assertInstanceOf(ToolCallResult::class, $result);
        $this->assertCount(1, $result->getContent());
        $this->assertSame('wikipedia_search', $result->getContent()[0]->getName());
        $this->assertSame(['term' => 'Chancellor of Germany'], $result->getContent()[0]->getArguments());
    }

    public function testItNormalizesTheFinishReasonButKeepsTheRawProviderValue()
    {
        $result = (new ResultConverter())->convert(new InMemoryRawResult($this->toolCallData('stop'), [], $this->httpResponseStub()));

        $finishReason = $result->getMetadata()->get('finish_reason');

        $this->assertInstanceOf(FinishReason::class, $finishReason);
        $this->assertTrue($finishReason->is(FinishReasonCase::TOOL_CALL));
        $this->assertSame('stop', $finishReason->getRaw());
    }

    public function testItStillHandlesTheSpecCompliantFinishReason()
    {
        $result = (new ResultConverter())->convert(new InMemoryRawResult($this->toolCallData('tool_calls'), [], $this->httpResponseStub()));

        $finishReason = $result->getMetadata()->get('finish_reason');

        $this->assertInstanceOf(ToolCallResult::class, $result);
        $this->assertInstanceOf(FinishReason::class, $finishReason);
        $this->assertTrue($finishReason->is(FinishReasonCase::TOOL_CALL));
        $this->assertSame('tool_calls', $finishReason->getRaw());
    }

    public function testItConvertsToolCallsReportedWithoutAnyFinishReason()
    {
        $withNull = $this->toolCallData(null);
        $withoutKey = $this->toolCallData(null);
        unset($withoutKey['choices'][0]['finish_reason']);

        foreach ([$withNull, $withoutKey] as $data) {
            $result = (new ResultConverter())->convert(new InMemoryRawResult($data, [], $this->httpResponseStub()));

            $this->assertInstanceOf(ToolCallResult::class, $result);
            $this->assertSame('wikipedia_search', $result->getContent()[0]->getName());
            $this->assertFalse($result->getMetadata()->has('finish_reason'));
        }
    }

    public function testItLeavesPlainTextResultsUntouched()
    {
        $data = [
            'choices' => [[
                'index' => 0,
                'message' => ['role' => 'assistant', 'content' => 'Ahoy!'],
                'finish_reason' => 'stop',
            ]],
        ];

        $result = (new ResultConverter())->convert(new InMemoryRawResult($data, [], $this->httpResponseStub()));

        $this->assertInstanceOf(TextResult::class, $result);
        $this->assertSame('Ahoy!', $result->getContent());
    }

    public function testItCompletesStreamedToolCallsOnAStopFinishReason()
    {
        $events = [
            ['choices' => [[
                'index' => 0,
                'delta' => ['role' => 'assistant', 'tool_calls' => [[
                    'index' => 0,
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => ['name' => 'wikipedia_search', 'arguments' => '{"term":"Chancellor of Germany"}'],
                ]]],
                'finish_reason' => null,
            ]]],
            ['choices' => [['index' => 0, 'delta' => [], 'finish_reason' => 'stop']]],
        ];

        $streamResult = (new ResultConverter())->convert(new InMemoryRawResult([], $events, $this->httpResponseStub()), ['stream' => true]);

        $deltas = iterator_to_array($streamResult->getContent());

        $this->assertInstanceOf(ToolCallStart::class, $deltas[0]);

        $completions = array_values(array_filter($deltas, static fn ($delta) => $delta instanceof ToolCallComplete));

        $this->assertCount(1, $completions);
        $this->assertSame('wikipedia_search', $completions[0]->getToolCalls()[0]->getName());
    }

    /**
     * @return array{choices: list<array{index: int, message: array{role: string, content: ?string, tool_calls: list<array{id: string, type: string, function: array{name: string, arguments: string}}>}, finish_reason: ?string}>}
     */
    private function toolCallData(?string $finishReason): array
    {
        return [
            'choices' => [[
                'index' => 0,
                'message' => [
                    'role' => 'assistant',
                    'content' => '',
                    'tool_calls' => [[
                        'id' => 'call_1',
                        'type' => 'function',
                        'function' => ['name' => 'wikipedia_search', 'arguments' => '{"term":"Chancellor of Germany"}'],
                    ]],
                ],
                'finish_reason' => $finishReason,
            ]],
        ];
    }

    private function httpResponseStub(): object
    {
        return new class {
            public function getStatusCode(): int
            {
                return 200;
            }
        };
    }
}
