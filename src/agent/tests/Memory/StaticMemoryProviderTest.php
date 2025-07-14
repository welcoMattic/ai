<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Tests\Memory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\Input;
use Symfony\AI\Agent\Memory\Memory;
use Symfony\AI\Agent\Memory\StaticMemoryProvider;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Model;

#[CoversClass(StaticMemoryProvider::class)]
#[UsesClass(Input::class)]
#[UsesClass(Memory::class)]
#[UsesClass(MessageBag::class)]
#[UsesClass(Model::class)]
#[Small]
final class StaticMemoryProviderTest extends TestCase
{
    #[Test]
    public function itsReturnsNullWhenNoFactsAreProvided(): void
    {
        $provider = new StaticMemoryProvider();

        $memory = $provider->loadMemory(new Input(
            self::createStub(Model::class),
            new MessageBag(),
            []
        ));

        self::assertCount(0, $memory);
    }

    #[Test]
    public function itDeliversFormattedFacts(): void
    {
        $provider = new StaticMemoryProvider(
            $fact1 = 'The sky is blue',
            $fact2 = 'Water is wet',
        );

        $memory = $provider->loadMemory(new Input(
            self::createStub(Model::class),
            new MessageBag(),
            []
        ));

        self::assertCount(1, $memory);
        self::assertInstanceOf(Memory::class, $memory[0]);
        $expectedContent = "## Static Memory\n\n- {$fact1}\n- {$fact2}";
        self::assertSame($expectedContent, $memory[0]->content);
    }
}
