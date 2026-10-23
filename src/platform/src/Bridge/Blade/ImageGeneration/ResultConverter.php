<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\ImageGeneration;

use Symfony\AI\Platform\Bridge\Blade\ImageGeneration;
use Symfony\AI\Platform\Bridge\Blade\UnexpectedResponseTrait;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Result\BinaryResult;
use Symfony\AI\Platform\Result\HttpStatusErrorHandlingTrait;
use Symfony\AI\Platform\Result\MultiPartResult;
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
        return $model instanceof ImageGeneration;
    }

    public function convert(RawResultInterface|RawHttpResult $result, array $options = []): ResultInterface
    {
        if ($result instanceof RawHttpResult) {
            $response = $result->getObject();

            $this->throwOnHttpError($response);

            if ($response->getStatusCode() >= 400) {
                throw $this->createUnexpectedResponseException($response);
            }
        }

        $data = $result->getData();

        if (!isset($data['data'][0])) {
            throw new RuntimeException('No image generated.');
        }

        $images = [];
        foreach ($data['data'] as $image) {
            if (!isset($image['b64_json'])) {
                throw new RuntimeException('The generated image has no "b64_json" field.');
            }

            // b64_json carries no MIME type; flux-2-klein returns PNG.
            $images[] = BinaryResult::fromBase64($image['b64_json'], 'image/png');
        }

        return 1 === \count($images) ? $images[0] : new MultiPartResult($images);
    }

    public function getTokenUsageExtractor(): ?TokenUsageExtractorInterface
    {
        return null;
    }
}
