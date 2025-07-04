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

use Psr\Cache\CacheItemPoolInterface;
use Symfony\AI\Agent\Chat\MessageStoreInterface;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\MessageBagInterface;

final readonly class CacheStore implements MessageStoreInterface
{
    public function __construct(
        private CacheItemPoolInterface $cache,
        private string $cacheKey,
        private int $ttl = 86400,
    ) {
    }

    public function save(MessageBagInterface $messages): void
    {
        $item = $this->cache->getItem($this->cacheKey);

        $item->set($messages);
        $item->expiresAfter($this->ttl);

        $this->cache->save($item);
    }

    public function load(): MessageBag
    {
        $item = $this->cache->getItem($this->cacheKey);

        return $item->isHit() ? $item->get() : new MessageBag();
    }

    public function clear(): void
    {
        $this->cache->deleteItem($this->cacheKey);
    }
}
