<?php

namespace Symfony\AI\Fixtures\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('tool_exception', description: 'This tool is broken', method: 'bar')]
final class ToolException
{
    public function bar(): string
    {
        throw new \Exception('Tool error.');
    }
}
