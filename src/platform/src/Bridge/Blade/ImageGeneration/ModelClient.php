<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\ImageGeneration;

use Symfony\AI\Platform\Bridge\Blade\ImageGeneration;
use Symfony\AI\Platform\Exception\InvalidArgumentException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelClientInterface;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
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
        return $model instanceof ImageGeneration;
    }

    /**
     * @param array<string, mixed> $options Sent as is, e.g. "n" or "size"
     */
    public function request(Model $model, array|string $payload, array $options = []): RawHttpResult
    {
        if (!\is_string($payload)) {
            throw new InvalidArgumentException(\sprintf('The image prompt must be a string, but "%s" was given to "%s".', get_debug_type($payload), self::class));
        }

        return new RawHttpResult($this->httpClient->request('POST', $this->baseUrl.'/v1/images/generations', [
            'auth_bearer' => $this->apiKey,
            'headers' => ['Content-Type' => 'application/json'],
            'json' => array_merge($options, ['model' => $model->getName(), 'prompt' => $payload]),
        ]));
    }
}
