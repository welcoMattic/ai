<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\TransformersPHP;

use Codewithkyrian\Transformers\Pipelines\Task;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Response\ObjectResponse;
use Symfony\AI\Platform\Response\RawResponseInterface;
use Symfony\AI\Platform\Response\TextResponse;
use Symfony\AI\Platform\ResponseConverterInterface;

final readonly class ResponseConverter implements ResponseConverterInterface
{
    public function supports(Model $model): bool
    {
        return true;
    }

    public function convert(RawResponseInterface $response, array $options = []): TextResponse|ObjectResponse
    {
        $data = $response->getRawData();

        if (Task::Text2TextGeneration === $options['task']) {
            $result = reset($data);

            return new TextResponse($result['generated_text']);
        }

        return new ObjectResponse($data);
    }
}
