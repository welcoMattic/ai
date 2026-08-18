<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\OrqAi\Factory;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = Factory::createPlatform(env('ORQ_API_KEY'), http_client());

$result = $platform->invoke('google-ai/gemini-embedding-2', <<<TEXT
    Once upon a time, there was a country called Foo. In this country, there was a village called Bar.
    In this village, there was a baker called Baz. Baz was a very good baker, and he was known for his
    delicious bread.
    TEXT);

print_vectors($result);
