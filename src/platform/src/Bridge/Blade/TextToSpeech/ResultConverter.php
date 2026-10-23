<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\TextToSpeech;

use Symfony\AI\Platform\Bridge\Blade\TextToSpeech;
use Symfony\AI\Platform\Bridge\Blade\UnexpectedResponseTrait;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Result\BinaryResult;
use Symfony\AI\Platform\Result\HttpStatusErrorHandlingTrait;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\ResultConverterInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsageExtractorInterface;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ResultConverter implements ResultConverterInterface
{
    use HttpStatusErrorHandlingTrait;
    use UnexpectedResponseTrait;

    public function supports(Model $model): bool
    {
        return $model instanceof TextToSpeech;
    }

    public function convert(RawResultInterface|RawHttpResult $result, array $options = []): ResultInterface
    {
        $response = $result->getObject();

        $this->throwOnHttpError($response);

        if (200 !== $response->getStatusCode()) {
            throw $this->createUnexpectedResponseException($response);
        }

        $contentType = $response->getHeaders()['content-type'][0] ?? null;

        return new BinaryResult($response->getContent(), null !== $contentType ? trim(explode(';', $contentType)[0]) : null);
    }

    public function getTokenUsageExtractor(): ?TokenUsageExtractorInterface
    {
        return null;
    }
}
