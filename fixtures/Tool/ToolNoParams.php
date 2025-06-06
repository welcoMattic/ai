<?php

namespace Symfony\AI\Fixtures\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('tool_no_params', 'A tool without parameters')]
final class ToolNoParams
{
    public function __invoke(): string
    {
        return 'Hello world!';
    }
}
