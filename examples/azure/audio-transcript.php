<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\Azure\OpenAi\Factory;
use Symfony\AI\Platform\Message\Content\Audio;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = Factory::createPlatform(
    env('AZURE_OPENAI_BASEURL'),
    env('AZURE_OPENAI_WHISPER_DEPLOYMENT'),
    '2024-06-01',
    env('AZURE_OPENAI_KEY'),
    http_client(),
);
$file = Audio::fromFile(dirname(__DIR__, 2).'/fixtures/audio.mp3');

$result = $platform->invoke('whisper-1', $file);

echo $result->asText().\PHP_EOL.\PHP_EOL;

$usage = $result->getMetadata()->get('usage');
if (null !== $usage) {
    echo 'Duration: '.$usage['seconds'].' seconds'.\PHP_EOL;
}
