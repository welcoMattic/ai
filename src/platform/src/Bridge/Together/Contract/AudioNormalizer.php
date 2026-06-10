<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Together\Contract;

use Symfony\AI\Platform\Bridge\Together\Together;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Contract\Normalizer\ModelContractNormalizer;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Message\Content\Audio;
use Symfony\AI\Platform\Model;

/**
 * Turns an audio message into the multipart file upload expected by the Together
 * transcription and translation endpoints.
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
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
            throw new RuntimeException(\sprintf('Cannot open the audio file at path "%s".', (string) $data->asPath()));
        }

        return ['file' => $file];
    }

    protected function supportedDataClass(): string
    {
        return Audio::class;
    }

    protected function supportsModel(Model $model): bool
    {
        return $model instanceof Together && $model->supports(Capability::SPEECH_TO_TEXT);
    }
}
