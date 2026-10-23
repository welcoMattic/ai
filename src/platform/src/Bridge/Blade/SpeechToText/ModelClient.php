<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\SpeechToText;

use Symfony\AI\Platform\Bridge\Blade\SpeechToText;
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
        return $model instanceof SpeechToText;
    }

    public function request(Model $model, array|string $payload, array $options = []): RawHttpResult
    {
        if (\is_string($payload)) {
            throw new InvalidArgumentException(\sprintf('Payload must be an array, but a string was given to "%s".', self::class));
        }

        if (!isset($payload['file'])) {
            throw new InvalidArgumentException('The payload must contain a "file" part with the audio to transcribe.');
        }

        return new RawHttpResult($this->httpClient->request('POST', $this->baseUrl.'/v1/audio/transcriptions', [
            'auth_bearer' => $this->apiKey,
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'body' => array_merge($options, $payload, ['model' => $model->getName()]),
        ]));
    }
}
