<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Fixtures\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\AI\Fixtures\SomeStructure;

#[AsTool('tool_array_multidimensional', 'A tool with multidimensional array parameters')]
final class ToolArrayMultidimensional
{
    /**
     * @param float[][]                $vectors
     * @param array<string, list<int>> $sequences
     * @param SomeStructure[][]        $objects
     */
    public function __invoke(array $vectors, array $sequences, array $objects): string
    {
        return 'Hello world!';
    }
}
