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
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelClientInterface as PlatformResponseFactory;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author André Lubian <lubiana123@gmail.com>
 */
final readonly class ModelClient implements PlatformResponseFactory
{
    private EventSourceHttpClient $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private string $hostUrl,
    ) {
        $this->httpClient = $httpClient instanceof EventSourceHttpClient ? $httpClient : new EventSourceHttpClient($httpClient);
    }

    public function supports(Model $model): bool
    {
        return $model instanceof Completions;
    }

    public function request(Model $model, array|string $payload, array $options = []): ResponseInterface
    {
        return $this->httpClient->request('POST', \sprintf('%s/v1/chat/completions', $this->hostUrl), [
            'json' => array_merge($options, $payload),
        ]);
    }
}
