<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade;

use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Builds the exception for error statuses that are not mapped to a dedicated exception.
 *
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
trait UnexpectedResponseTrait
{
    private function createUnexpectedResponseException(ResponseInterface $response): RuntimeException
    {
        $body = $response->getContent(false);
        $data = json_decode($body, true);

        if (\is_array($data) && \is_array($data['error'] ?? null)) {
            $error = $data['error'];

            return new RuntimeException(\sprintf('Error "%s"-%s (%s): "%s".', $error['code'] ?? '-', $error['type'] ?? '-', $error['param'] ?? '-', $error['message'] ?? '-'));
        }

        return new RuntimeException(\sprintf('Unexpected response code %d: "%s"', $response->getStatusCode(), $body));
    }
}
