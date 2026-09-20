<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\Venice\Factory;
use Symfony\AI\Platform\Message\Content\Text;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = Factory::createPlatform(
    env('VENICE_API_KEY'),
    httpClient: http_client(),
    // Replaying a cassette serves the status polls instantly, so skip the real waiting.
    clock: is_replay() ? clock() : null,
);

// The video API requires `duration` and `aspect_ratio`, and the allowed values differ per model -
// `GET /models` reports them together with `resolutions` under `model_spec.constraints`. Generation
// is billed per pixel-second, so the shortest duration and lowest resolution keep an example cheap.
$result = $platform->invoke('pixverse-c1-text-to-video', new Text('A serene ocean with dolphins jumping at sunset'), [
    'duration' => '3s',
    'aspect_ratio' => '16:9',
    'resolution' => '360p',
]);

$result->asFile(__DIR__.'/text-to-video.mp4');

echo 'Video saved to text-to-video.mp4'.\PHP_EOL;
