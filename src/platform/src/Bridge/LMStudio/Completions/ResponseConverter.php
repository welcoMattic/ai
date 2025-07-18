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
use Symfony\AI\Platform\Response\ResponseInterface as LlmResponse;
use Symfony\AI\Platform\ResponseConverterInterface as PlatformResponseConverter;
use Symfony\Contracts\HttpClient\ResponseInterface as HttpResponse;

/**
 * @author André Lubian <lubiana123@gmail.com>
 */
final class ResponseConverter implements PlatformResponseConverter
{
    public function __construct(
        private readonly OpenAIResponseConverter $gptResponseConverter = new OpenAIResponseConverter(),
    ) {
    }

    public function supports(Model $model): bool
    {
        return $model instanceof Completions;
    }

    public function convert(HttpResponse $response, array $options = []): LlmResponse
    {
        return $this->gptResponseConverter->convert($response, $options);
    }
}
