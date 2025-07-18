<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\LMStudio\Completions;

use Symfony\AI\Platform\Bridge\LMStudio\Completions;
use Symfony\AI\Platform\Bridge\OpenAI\GPT\ResponseConverter as OpenAIResponseConverter;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Response\RawResponseInterface;
use Symfony\AI\Platform\Response\ResponseInterface;
use Symfony\AI\Platform\ResponseConverterInterface;

/**
 * @author André Lubian <lubiana123@gmail.com>
 */
final class ResponseConverter implements ResponseConverterInterface
{
    public function __construct(
        private readonly OpenAIResponseConverter $gptResponseConverter = new OpenAIResponseConverter(),
    ) {
    }

    public function supports(Model $model): bool
    {
        return $model instanceof Completions;
    }

    public function convert(RawResponseInterface $response, array $options = []): ResponseInterface
    {
        return $this->gptResponseConverter->convert($response, $options);
    }
}
