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

use Symfony\AI\Platform\Contract\JsonSchema\Factory;
use Symfony\AI\Platform\Contract\Normalizer\ToolNormalizer as BaseToolNormalizer;
use Symfony\AI\Platform\Tool\Tool;

/**
 * Together rejects a tool definition without a `parameters` field, which the base normalizer
 * omits for a tool taking no arguments, so an empty schema object is sent instead.
 *
 * @phpstan-import-type JsonSchema from Factory
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class ToolNormalizer extends BaseToolNormalizer
{
    /**
     * @param Tool $data
     *
     * @return array{
     *     type: 'function',
     *     function: array{
     *         name: string,
     *         description: string,
     *         parameters: JsonSchema|\stdClass
     *     }
     * }
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $data->getName(),
                'description' => $data->getDescription(),
                'parameters' => $data->getParameters() ?? new \stdClass(),
            ],
        ];
    }
}
