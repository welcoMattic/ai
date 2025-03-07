<?php

declare(strict_types=1);

namespace PhpLlm\McpSdk\Server\RequestHandler;

use PhpLlm\LlmChain\Chain\ToolBox\Metadata;
use PhpLlm\LlmChain\Chain\ToolBox\ToolBoxInterface;
use PhpLlm\McpSdk\Message\Notification;
use PhpLlm\McpSdk\Message\Request;
use PhpLlm\McpSdk\Message\Response;

final class ToolListHandler extends BaseRequestHandler
{
    public function __construct(
        private readonly ToolBoxInterface $toolBox,
    ) {
    }

    public function createResponse(Request|Notification $message): Response
    {
        return new Response($message->id, [
            'tools' => array_map(function (Metadata $tool) {
                return [
                    'name' => $tool->name,
                    'description' => $tool->description,
                    'inputSchema' => $tool->parameters ?? [
                        'type' => 'object',
                        '$schema' => 'http://json-schema.org/draft-07/schema#',
                    ],
                ];
            }, $this->toolBox->getMap()),
        ]);
    }

    protected function supportedMethod(): string
    {
        return 'tools/list';
    }
}
