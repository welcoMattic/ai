<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\OpenAI\DallE;

use Symfony\AI\Platform\Bridge\OpenAI\DallE;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Response\RawResponseInterface;
use Symfony\AI\Platform\Response\ResponseInterface;
use Symfony\AI\Platform\ResponseConverterInterface;

/**
 * @see https://platform.openai.com/docs/api-reference/images/create
 *
 * @author Denis Zunke <denis.zunke@gmail.com>
 */
final readonly class ResponseConverter implements ResponseConverterInterface
{
    public function supports(Model $model): bool
    {
        return $model instanceof DallE;
    }

    public function convert(RawResponseInterface $response, array $options = []): ResponseInterface
    {
        $response = $response->getRawData();

        if (!isset($response['data'][0])) {
            throw new RuntimeException('No image generated.');
        }

        $images = [];
        foreach ($response['data'] as $image) {
            if ('url' === $options['response_format']) {
                $images[] = new UrlImage($image['url']);

                continue;
            }

            $images[] = new Base64Image($image['b64_json']);
        }

        return new ImageResponse($image['revised_prompt'] ?? null, ...$images);
    }
}
