<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\TextToSpeech;

use Symfony\AI\Platform\Bridge\Blade\TextToSpeech;
use Symfony\AI\Platform\Exception\InvalidArgumentException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelClientInterface;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @see https://api.models.blade.sh/docs/models.md
 *
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ModelClient implements ModelClientInterface
{
    private readonly string $baseUrl;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        string $baseUrl,
        #[\SensitiveParameter] private readonly string $apiKey,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function supports(Model $model): bool
    {
        return $model instanceof TextToSpeech;
    }

    /**
     * @param array<string, mixed> $options Sent as is, e.g. "voice" or "response_format"
     */
    public function request(Model $model, array|string $payload, array $options = []): RawHttpResult
    {
        if (isset($options['stream_format']) || isset($options['stream'])) {
            throw new InvalidArgumentException('Streaming text to speech results is not supported yet.');
        }

        if (\is_string($payload)) {
            $input = $payload;
        } else {
            if (!isset($payload['text'])) {
                throw new InvalidArgumentException('The payload must be a string or an array with a "text" key.');
            }

            $input = $payload['text'];
        }

        return new RawHttpResult($this->httpClient->request('POST', $this->baseUrl.'/v1/audio/speech', [
            'auth_bearer' => $this->apiKey,
            'headers' => ['Content-Type' => 'application/json'],
            'json' => array_merge($options, ['model' => $model->getName(), 'input' => $input]),
        ]));
    }
}
