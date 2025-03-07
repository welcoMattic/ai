<?php

declare(strict_types=1);

namespace PhpLlm\McpSdk\Server\NotificationHandler;

use PhpLlm\McpSdk\Message\Notification;
use PhpLlm\McpSdk\Server\NotificationHandler;

abstract class BaseNotificationHandler implements NotificationHandler
{
    public function supports(Notification $message): bool
    {
        return $message->method === sprintf('notifications/%s', $this->supportedNotification());
    }

    abstract protected function supportedNotification(): string;
}
