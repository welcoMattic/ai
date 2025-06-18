<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Google\Contract;

use Symfony\AI\Platform\Bridge\Google\Gemini;
use Symfony\AI\Platform\Contract\JsonSchema\Factory;
use Symfony\AI\Platform\Contract\Normalizer\ModelContractNormalizer;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Tool\Tool;

/**
 * @author Valtteri R <valtzu@gmail.com>
 *
 * @phpstan-import-type JsonSchema from Factory
 */
final class ToolNormalizer extends ModelContractNormalizer
{
    protected function supportedDataClass(): string
    {
        return Tool::class;
    }

    protected function supportsModel(Model $model): bool
    {
        return $model instanceof Gemini;
    }

    /**
     * @param Tool $data
     *
     * @return array{
     *     functionDeclarations: array{
     *         name: string,
     *         description: string,
     *         parameters: JsonSchema|array{type: 'object'}
     *     }[]
     * }
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $parameters = $data->parameters;
        unset($parameters['additionalProperties']);

        return [
            'functionDeclarations' => [
                [
                    'description' => $data->description,
                    'name' => $data->name,
                    'parameters' => $parameters,
                ],
            ],
        ];
    }
}
