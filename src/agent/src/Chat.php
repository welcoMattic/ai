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

use Symfony\AI\Agent\Chat\MessageStoreInterface;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBagInterface;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\Response\TextResponse;

final readonly class Chat implements ChatInterface
{
    public function __construct(
        private AgentInterface $agent,
        private MessageStoreInterface $store,
    ) {
    }

    public function initiate(MessageBagInterface $messages): void
    {
        $this->store->clear();
        $this->store->save($messages);
    }

    public function submit(UserMessage $message): AssistantMessage
    {
        $messages = $this->store->load();

        $messages->add($message);
        $response = $this->agent->call($messages);

        \assert($response instanceof TextResponse);

        $assistantMessage = Message::ofAssistant($response->getContent());
        $messages->add($assistantMessage);

        $this->store->save($messages);

        return $assistantMessage;
    }
}
