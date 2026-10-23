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
use Symfony\AI\Platform\Bridge\Blade\UnexpectedResponseTrait;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Result\HttpStatusErrorHandlingTrait;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\TextResult;
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
        return $model instanceof SpeechToText;
    }

    public function convert(RawResultInterface|RawHttpResult $result, array $options = []): ResultInterface
    {
        $response = $result->getObject();

        $this->throwOnHttpError($response);

        if ($response->getStatusCode() >= 400) {
            throw $this->createUnexpectedResponseException($response);
        }

        // The "text", "srt" and "vtt" response formats are plain text, not JSON.
        if (!str_contains($response->getHeaders(false)['content-type'][0] ?? '', 'json')) {
            return new TextResult($response->getContent());
        }

        $data = $result->getData();

        if (!isset($data['text'])) {
            throw new RuntimeException(\sprintf('The response is missing the required "text" field. Response data: "%s"', json_encode($data)));
        }

        $textResult = new TextResult($data['text']);

        foreach (['usage', 'language', 'duration', 'segments'] as $key) {
            if (isset($data[$key])) {
                $textResult->getMetadata()->add($key, $data[$key]);
            }
        }

        return $textResult;
    }

    public function getTokenUsageExtractor(): ?TokenUsageExtractorInterface
    {
        return null;
    }
}
