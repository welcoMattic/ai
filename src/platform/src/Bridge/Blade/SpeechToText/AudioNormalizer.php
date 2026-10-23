<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\SpeechToText;

use Symfony\AI\Platform\Bridge\Blade\SpeechToText;
use Symfony\AI\Platform\Contract\Normalizer\ModelContractNormalizer;
use Symfony\AI\Platform\Exception\InvalidArgumentException;
use Symfony\AI\Platform\Message\Content\Audio;
use Symfony\AI\Platform\Model;

/**
 * The transcription endpoint takes the audio as a multipart file instead of an "input_audio" chat part.
 *
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class AudioNormalizer extends ModelContractNormalizer
{
    /**
     * @param Audio $data
     *
     * @return array{file: resource}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $file = $data->asResource();

        if (false === $file) {
            throw new InvalidArgumentException(\sprintf('Unable to open the audio file "%s".', $data->getFilename()));
        }

        return ['file' => $file];
    }

    protected function supportedDataClass(): string
    {
        return Audio::class;
    }

    protected function supportsModel(Model $model): bool
    {
        return $model instanceof SpeechToText;
    }
}
