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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final readonly class SessionStore implements MessageStoreInterface
{
    private SessionInterface $session;

    public function __construct(
        RequestStack $requestStack,
        private string $sessionKey = 'messages',
    ) {
        $this->session = $requestStack->getSession();
    }

    public function save(MessageBagInterface $messages): void
    {
        $this->session->set($this->sessionKey, $messages);
    }

    public function load(): MessageBagInterface
    {
        return $this->session->get($this->sessionKey, new MessageBag());
    }

    public function clear(): void
    {
        $this->session->remove($this->sessionKey);
    }
}
