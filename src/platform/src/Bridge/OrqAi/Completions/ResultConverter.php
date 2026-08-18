<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\OrqAi\Completions;

use Symfony\AI\Platform\Bridge\Generic\Completions\ResultConverter as GenericResultConverter;
use Symfony\AI\Platform\FinishReason\FinishReason;
use Symfony\AI\Platform\FinishReason\FinishReasonCase;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\Result\ToolCallResult;

/**
 * Depending on the upstream provider, the Orq.ai router reports `finish_reason: "stop"` even when
 * the model asked for a tool call, instead of the `"tool_calls"` value the OpenAI schema defines:
 * Google does, Mistral does not. The generic converter keys the conversion on that field, so it
 * would build an empty text result and silently drop the tool calls. Both the buffered and the
 * streamed path therefore key on the presence of the tool calls themselves here.
 *
 * On the buffered path the normalized case is corrected to `TOOL_CALL` and the raw provider wording
 * is preserved. The streamed `finish_reason` metadata is left as the shared conversion trait maps
 * it, so a streamed tool call still surfaces the reason the provider sent.
 *
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ResultConverter extends GenericResultConverter
{
    protected function convertChoice(array $choice): ToolCallResult|TextResult
    {
        $toolCalls = $choice['message']['tool_calls'] ?? [];
        // The router types the finish reason as nullable, so read it defensively: FinishReason
        // requires a string and would raise a \TypeError, which is not a platform exception.
        $finishReason = $choice['finish_reason'] ?? null;

        if ([] === $toolCalls || 'tool_calls' === $finishReason) {
            return parent::convertChoice($choice);
        }

        return $this->withFinishReason(
            new ToolCallResult(array_map($this->convertToolCall(...), $toolCalls)),
            // Left unset when the provider sent no reason, like FinishReasonMapper does.
            null === $finishReason || '' === $finishReason ? null : new FinishReason(FinishReasonCase::TOOL_CALL, $finishReason),
        );
    }

    protected function isToolCallsStreamFinished(array $data): bool
    {
        // Only called once tool call deltas have been collected, so any terminal chunk ends them.
        return null !== ($data['choices'][0]['finish_reason'] ?? null);
    }
}
