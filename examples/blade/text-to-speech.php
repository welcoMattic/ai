<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\Blade\Factory;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = Factory::createPlatform(env('BLADE_API_KEY'), http_client());

$result = $platform->invoke('kokoro-82m', 'Welcome to Symfony AI, the PHP framework for building AI applications.', [
    'voice' => 'af_heart',
]);

$result->asFile(__DIR__.'/text-to-speech.wav');

echo 'Audio saved to text-to-speech.wav'.\PHP_EOL;
