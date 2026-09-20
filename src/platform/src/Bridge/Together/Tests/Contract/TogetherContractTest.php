<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Together\Tests\Contract;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Together\Contract\TogetherContract;
use Symfony\AI\Platform\Bridge\Together\Together;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Message\Content\Audio;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class TogetherContractTest extends TestCase
{
    public function testItNormalizesAudioForSpeechToText()
    {
        $audio = Audio::fromFile(\dirname(__DIR__, 7).'/fixtures/audio.mp3');
        $model = new Together('openai/whisper-large-v3', [Capability::SPEECH_TO_TEXT, Capability::INPUT_AUDIO]);

        $payload = TogetherContract::create()->createRequestPayload($model, $audio);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('file', $payload);
        $this->assertIsResource($payload['file']);
        $this->assertSame($audio->asBinary(), stream_get_contents($payload['file']));
    }

    public function testItLeavesAudioAloneForModelsWithoutSpeechToText()
    {
        $audio = Audio::fromFile(\dirname(__DIR__, 7).'/fixtures/audio.mp3');
        $model = new Together('openai/gpt-oss-120b', [Capability::INPUT_MESSAGES, Capability::INPUT_AUDIO]);

        $payload = TogetherContract::create()->createRequestPayload($model, $audio);

        $this->assertIsArray($payload);
        $this->assertArrayNotHasKey('file', $payload);
    }
}
