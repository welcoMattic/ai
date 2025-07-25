<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\McpSdk\Exception;

use Symfony\AI\McpSdk\Capability\Resource\ResourceRead;

final class ResourceReadException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly ResourceRead $readRequest,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(\sprintf('Reading resource "%s" failed with error: %s', $readRequest->uri, $previous?->getMessage() ?? ''), previous: $previous);
    }
}
