<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Together;

use Symfony\AI\Platform\Bridge\Generic\Completions\TokenUsageExtractor as GenericTokenUsageExtractor;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsageExtractorInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsageInterface;

/**
 * Reads the OpenAI-compatible `usage` object, except on `/v1/audio/speech`, which answers
 * with raw audio: decoding that body as JSON to look for token usage would throw.
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class TokenUsageExtractor implements TokenUsageExtractorInterface
{
    public function extract(RawResultInterface $rawResult, array $options = []): ?TokenUsageInterface
    {
        if ($rawResult instanceof RawHttpResult) {
            $url = $rawResult->getObject()->getInfo('url');

            if (\is_string($url) && str_contains($url, '/audio/speech')) {
                return null;
            }
        }

        return (new GenericTokenUsageExtractor())->extract($rawResult, $options);
    }
}
