<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Toolbox;

use Symfony\AI\Platform\Response\ToolCall;
use Symfony\AI\Platform\Tool\Tool;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Valtteri R <valtzu@gmail.com>
 */
final readonly class ToolCallArgumentResolver implements ToolCallArgumentResolverInterface
{
    public function __construct(
        private DenormalizerInterface $denormalizer = new Serializer([new DateTimeNormalizer(), new ObjectNormalizer()]),
    ) {
    }

    public function resolveArguments(object $tool, Tool $metadata, ToolCall $toolCall): array
    {
        $method = new \ReflectionMethod($metadata->reference->class, $metadata->reference->method);

        /** @var array<string, \ReflectionProperty> $parameters */
        $parameters = array_column($method->getParameters(), null, 'name');
        $arguments = [];

        foreach ($toolCall->arguments as $name => $value) {
            $arguments[$name] = $this->denormalizer->denormalize($value, (string) $parameters[$name]->getType());
        }

        return $arguments;
    }
}
