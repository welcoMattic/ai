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
use Symfony\AI\Platform\Exception\InvalidArgumentException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Response\ObjectResponse;
use Symfony\AI\Platform\Response\ResponseInterface;
use Symfony\AI\Platform\Response\ResponsePromise;
use Symfony\AI\Platform\Response\TextResponse;

use function Codewithkyrian\Transformers\Pipelines\pipeline;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
final class Platform implements PlatformInterface
{
    public function request(Model $model, object|array|string $input, array $options = []): ResponsePromise
    {
        if (null === $task = $options['task'] ?? null) {
            throw new InvalidArgumentException('The task option is required.');
        }

        $pipeline = pipeline(
            $task,
            $model->getName(),
            $options['quantized'] ?? true,
            $options['config'] ?? null,
            $options['cacheDir'] ?? null,
            $options['revision'] ?? 'main',
            $options['modelFilename'] ?? null,
        );
        $execution = new PipelineExecution($pipeline, $input);

        return new ResponsePromise($this->convertResponse(...), new RawPipelineResponse($execution), $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function convertResponse(PipelineExecution $pipelineExecution, array $options): ResponseInterface
    {
        $data = $pipelineExecution->getResult();

        return match ($options['task']) {
            Task::Text2TextGeneration => new TextResponse($data[0]['generated_text']),
            default => new ObjectResponse($data),
        };
    }
}
