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

use Symfony\AI\Platform\Bridge\Blade\ImageGeneration;
use Symfony\AI\Platform\Bridge\Blade\ModelCatalog;
use Symfony\AI\Platform\Bridge\Blade\SpeechToText;
use Symfony\AI\Platform\Bridge\Blade\TextToSpeech;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use Symfony\AI\Platform\Test\ModelCatalogTestCase;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ModelCatalogTest extends ModelCatalogTestCase
{
    public static function modelsProvider(): iterable
    {
        yield 'qwen3.6-35b-a3b' => ['qwen3.6-35b-a3b', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::INPUT_IMAGE, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED, Capability::TOOL_CALLING, Capability::THINKING]];
        yield 'qwen3.8-27b' => ['qwen3.8-27b', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::INPUT_IMAGE, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED, Capability::TOOL_CALLING, Capability::THINKING]];
        yield 'gemma-4-26b-a4b' => ['gemma-4-26b-a4b', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::INPUT_IMAGE, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED, Capability::TOOL_CALLING]];
        yield 'laguna-xs-2.1' => ['laguna-xs-2.1', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED, Capability::TOOL_CALLING, Capability::THINKING]];
        yield 'gpt-oss-20b' => ['gpt-oss-20b', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED, Capability::TOOL_CALLING, Capability::THINKING]];
        yield 'hypernova-60b' => ['hypernova-60b', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED, Capability::TOOL_CALLING, Capability::THINKING]];
        yield 'nemotron3-nano-30b' => ['nemotron3-nano-30b', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED, Capability::TOOL_CALLING, Capability::THINKING]];
        yield 'nemotron3-omni-30b' => ['nemotron3-omni-30b', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::INPUT_IMAGE, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED, Capability::TOOL_CALLING, Capability::THINKING]];
        yield 'qwen3-vl-30b-a3b' => ['qwen3-vl-30b-a3b', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::INPUT_IMAGE, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED, Capability::TOOL_CALLING]];
        yield 'deepseek-ocr-2' => ['deepseek-ocr-2', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::INPUT_IMAGE, Capability::OUTPUT_TEXT, Capability::OUTPUT_STRUCTURED]];
        yield 'voxtral-mini-3b' => ['voxtral-mini-3b', CompletionsModel::class, [Capability::INPUT_MESSAGES, Capability::INPUT_AUDIO, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::OUTPUT_STRUCTURED]];

        yield 'qwen3-embedding-8b' => ['qwen3-embedding-8b', EmbeddingsModel::class, [Capability::INPUT_TEXT, Capability::EMBEDDINGS]];
        yield 'whisper-large-v3-turbo' => ['whisper-large-v3-turbo', SpeechToText::class, [Capability::INPUT_AUDIO, Capability::OUTPUT_TEXT, Capability::SPEECH_TO_TEXT]];
        yield 'kokoro-82m' => ['kokoro-82m', TextToSpeech::class, [Capability::INPUT_TEXT, Capability::OUTPUT_AUDIO, Capability::TEXT_TO_SPEECH]];
        yield 'flux-2-klein' => ['flux-2-klein', ImageGeneration::class, [Capability::INPUT_TEXT, Capability::OUTPUT_IMAGE, Capability::TEXT_TO_IMAGE]];
    }

    protected function createModelCatalog(): ModelCatalogInterface
    {
        return new ModelCatalog();
    }
}
