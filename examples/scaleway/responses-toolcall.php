<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Bridge\Clock\Clock;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Bridge\Scaleway\Factory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = Factory::createPlatform(env('SCALEWAY_SECRET_KEY'), http_client());

$toolbox = new Toolbox([new Clock(clock())], logger: logger());

// gpt-oss-120b uses the Open Responses bridge which supports function calling
$agent = new Agent($platform, 'gpt-oss-120b', toolbox: $toolbox);

$messages = new MessageBag(Message::ofUser('What date and time is it right now?'));
$result = $agent->call($messages);

echo $result->asText().\PHP_EOL;
