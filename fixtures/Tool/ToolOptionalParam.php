<?php

namespace Symfony\AI\Fixtures\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('tool_optional_param', 'A tool with one optional parameter', method: 'bar')]
final class ToolOptionalParam
{
    /**
     * @param string $text   The text given to the tool
     * @param int    $number A number given to the tool
     */
    public function bar(string $text, int $number = 3): string
    {
        return \sprintf('%s says "%d".', $text, $number);
    }
}
