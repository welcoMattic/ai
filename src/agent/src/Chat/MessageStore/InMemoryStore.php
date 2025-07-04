<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Chat\MessageStore;

use Symfony\AI\Agent\Chat\MessageStoreInterface;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\MessageBagInterface;

final class InMemoryStore implements MessageStoreInterface
{
    private MessageBagInterface $messages;

    public function save(MessageBagInterface $messages): void
    {
        $this->messages = $messages;
    }

    public function load(): MessageBagInterface
    {
        return $this->messages ?? new MessageBag();
    }

    public function clear(): void
    {
        $this->messages = new MessageBag();
    }
}
