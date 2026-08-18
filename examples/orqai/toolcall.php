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
use Symfony\AI\Agent\Bridge\Wikipedia\Wikipedia;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Bridge\OrqAi\Factory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = Factory::createPlatform(env('ORQ_API_KEY'), http_client());

$wikipedia = new Wikipedia(http_client());
$toolbox = new Toolbox([$wikipedia], logger: logger());
$processor = new AgentProcessor($toolbox);
// Gemini 3 models cannot run an agent loop through the router: continuing a tool-calling
// conversation requires a thought signature that the OpenAI-compatible response does not carry.
$agent = new Agent($platform, 'openai/gpt-4o-mini', [$processor], [$processor]);

$messages = new MessageBag(Message::ofUser('Who is the current chancellor of Germany?'));
$result = $agent->call($messages);

echo $result->getContent().\PHP_EOL;
