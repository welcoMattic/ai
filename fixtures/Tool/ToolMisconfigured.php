<?php

namespace Symfony\AI\Fixtures\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('tool_misconfigured', description: 'This tool is misconfigured, see method', method: 'foo')]
final class ToolMisconfigured
{
    public function bar(): string
    {
        return 'Wrong Config Attribute';
    }
}
