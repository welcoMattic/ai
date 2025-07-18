<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\OpenAI\Whisper;

use Symfony\AI\Platform\Bridge\OpenAI\Whisper;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Response\RawResponseInterface;
use Symfony\AI\Platform\Response\ResponseInterface;
use Symfony\AI\Platform\Response\TextResponse;
use Symfony\AI\Platform\ResponseConverterInterface as BaseResponseConverter;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
final class ResponseConverter implements BaseResponseConverter
{
    public function supports(Model $model): bool
    {
        return $model instanceof Whisper;
    }

    public function convert(RawResponseInterface $response, array $options = []): ResponseInterface
    {
        $data = $response->getRawData();

        return new TextResponse($data['text']);
    }
}
