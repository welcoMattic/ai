<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Together\Tests\Contract;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Together\Contract\ToolNormalizer;
use Symfony\AI\Platform\Tool\ExecutionReference;
use Symfony\AI\Platform\Tool\Tool;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class ToolNormalizerTest extends TestCase
{
    public function testItSendsAnEmptySchemaForAToolWithoutParameters()
    {
        $tool = new Tool(new ExecutionReference('stdClass'), 'clock', 'Returns the current time');

        $normalized = (new ToolNormalizer())->normalize($tool);

        $this->assertEquals(new \stdClass(), $normalized['function']['parameters']);
        $this->assertSame('{"name":"clock","description":"Returns the current time","parameters":{}}', json_encode($normalized['function']));
    }

    public function testItKeepsTheSchemaOfAToolWithParameters()
    {
        $parameters = [
            'type' => 'object',
            'properties' => ['city' => ['type' => 'string', 'description' => 'The city to look up']],
            'required' => ['city'],
            'additionalProperties' => false,
        ];
        $tool = new Tool(new ExecutionReference('stdClass'), 'weather', 'Returns the weather', $parameters);

        $normalized = (new ToolNormalizer())->normalize($tool);

        $this->assertSame('function', $normalized['type']);
        $this->assertSame($parameters, $normalized['function']['parameters']);
    }
}
