<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent;

use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\MessageBagInterface;
use Symfony\AI\Platform\Message\UserMessage;

interface ChatInterface
{
    public function initiate(MessageBagInterface $messages): void;

    public function submit(UserMessage $message): AssistantMessage;
}
