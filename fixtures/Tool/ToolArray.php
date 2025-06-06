<?php

namespace Symfony\AI\Fixtures\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('tool_no_params', 'A tool without parameters')]
final class ToolArray
{
    /**
     * @param string[]  $urls
     * @param list<int> $ids
     */
    public function __invoke(array $urls, array $ids): string
    {
        return 'Hello world!';
    }
}
