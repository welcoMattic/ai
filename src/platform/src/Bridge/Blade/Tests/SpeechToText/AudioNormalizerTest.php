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
use Symfony\AI\Platform\Bridge\Blade\SpeechToText\AudioNormalizer;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Contract;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Message\Content\Audio;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class AudioNormalizerTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'blade-audio');
        file_put_contents($this->file, 'fake-audio-bytes');
    }

    protected function tearDown(): void
    {
        unlink($this->file);
    }

    public function testItSupportsAudioForTheSpeechToTextModel()
    {
        $normalizer = new AudioNormalizer();
        $audio = Audio::fromFile($this->file);

        $this->assertTrue($normalizer->supportsNormalization($audio, context: [Contract::CONTEXT_MODEL => new SpeechToText('whisper-large-v3-turbo')]));
        $this->assertFalse($normalizer->supportsNormalization($audio, context: [Contract::CONTEXT_MODEL => new CompletionsModel('voxtral-mini-3b')]));
        $this->assertFalse($normalizer->supportsNormalization('text', context: [Contract::CONTEXT_MODEL => new SpeechToText('whisper-large-v3-turbo')]));
    }

    public function testItNormalizesAudioToAFileResource()
    {
        $payload = (new AudioNormalizer())->normalize(Audio::fromFile($this->file));

        $this->assertSame(['file'], array_keys($payload));
        $this->assertIsResource($payload['file']);
        $this->assertSame('fake-audio-bytes', stream_get_contents($payload['file']));
    }

    public function testItFailsOnAudioWithoutFile()
    {
        $this->expectException(RuntimeException::class);

        (new AudioNormalizer())->normalize(Audio::fromDataUrl('data:audio/mpeg;base64,'.base64_encode('fake-audio-bytes')));
    }
}
