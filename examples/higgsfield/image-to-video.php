<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\Higgsfield\Factory;
use Symfony\AI\Platform\Message\Content\Image;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = Factory::createPlatform(
    apiKey: env('HIGGSFIELD_API_KEY'),
    apiSecret: env('HIGGSFIELD_API_SECRET'),
    httpClient: http_client(),
    // Replaying a cassette serves the status polls instantly, so skip the real waiting.
    clock: is_replay() ? clock() : null,
);

$result = $platform->invoke('kling-2.5-i2v', Image::fromFile(dirname(__DIR__, 2).'/fixtures/image.jpg'), [
    'prompt' => 'Slowly zoom into the scene',
    'duration' => 5,
]);

$result->asFile(__DIR__.'/image-to-video.mp4');

echo 'Video saved to image-to-video.mp4'.\PHP_EOL;
