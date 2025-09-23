<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\ElevenLabs\ElevenLabs;
use Symfony\AI\Platform\Bridge\ElevenLabs\PlatformFactory;
use Symfony\AI\Platform\Message\Content\Text;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = PlatformFactory::create(
    apiKey: env('ELEVEN_LABS_API_KEY'),
    httpClient: http_client(),
);
$model = new ElevenLabs(ElevenLabs::ELEVEN_MULTILINGUAL_V2, [
    'voice' => 'Dslrhjl3ZpzrctukrQSN', // Brad (https://elevenlabs.io/app/voice-library?voiceId=Dslrhjl3ZpzrctukrQSN)
]);

$result = $platform->invoke($model, new Text('Hello world'));

echo $result->asBinary().\PHP_EOL;
