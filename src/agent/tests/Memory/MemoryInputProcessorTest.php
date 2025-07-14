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
use Symfony\AI\Agent\Memory\MemoryInputProcessor;
use Symfony\AI\Agent\Memory\MemoryProviderInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Model;

#[CoversClass(MemoryInputProcessor::class)]
#[UsesClass(MemoryProviderInterface::class)]
#[UsesClass(Input::class)]
#[UsesClass(MessageBag::class)]
#[UsesClass(Model::class)]
#[UsesClass(Memory::class)]
#[UsesClass(Message::class)]
#[Small]
final class MemoryInputProcessorTest extends TestCase
{
    #[Test]
    public function itIsDoingNothingOnInactiveMemory(): void
    {
        $memoryProvider = $this->createMock(MemoryProviderInterface::class);
        $memoryProvider->expects($this->never())->method(self::anything());

        $memoryInputProcessor = new MemoryInputProcessor($memoryProvider);
        $memoryInputProcessor->processInput($input = new Input(
            self::createStub(Model::class),
            new MessageBag(),
            ['use_memory' => false]
        ));

        self::assertArrayNotHasKey('use_memory', $input->getOptions());
    }

    #[Test]
    public function itIsDoingNothingWhenThereAreNoProviders(): void
    {
        $memoryInputProcessor = new MemoryInputProcessor();
        $memoryInputProcessor->processInput($input = new Input(
            self::createStub(Model::class),
            new MessageBag(),
            ['use_memory' => true]
        ));

        self::assertArrayNotHasKey('use_memory', $input->getOptions());
    }

    #[Test]
    public function itIsAddingMemoryToSystemPrompt(): void
    {
        $firstMemoryProvider = $this->createMock(MemoryProviderInterface::class);
        $firstMemoryProvider->expects($this->once())
            ->method('loadMemory')
            ->willReturn([new Memory('First memory content')]);

        $secondMemoryProvider = $this->createMock(MemoryProviderInterface::class);
        $secondMemoryProvider->expects($this->once())
            ->method('loadMemory')
            ->willReturn([]);

        $memoryInputProcessor = new MemoryInputProcessor(
            $firstMemoryProvider,
            $secondMemoryProvider,
        );

        $memoryInputProcessor->processInput($input = new Input(
            self::createStub(Model::class),
            new MessageBag(Message::forSystem('You are a helpful and kind assistant.')),
            []
        ));

        self::assertArrayNotHasKey('use_memory', $input->getOptions());
        self::assertSame(
            <<<MARKDOWN
                You are a helpful and kind assistant.

                # Conversation Memory
                This is the memory I have found for this conversation. The memory has more weight to answer user input,
                so try to answer utilizing the memory as much as possible. Your answer must be changed to fit the given
                memory. If the memory is irrelevant, ignore it. Do not reply to the this section of the prompt and do not
                reference it as this is just for your reference.

                First memory content
                MARKDOWN,
            $input->messages->getSystemMessage()->content,
        );
    }

    #[Test]
    public function itIsAddingMemoryToSystemPromptEvenItIsEmpty(): void
    {
        $firstMemoryProvider = $this->createMock(MemoryProviderInterface::class);
        $firstMemoryProvider->expects($this->once())
            ->method('loadMemory')
            ->willReturn([new Memory('First memory content')]);

        $memoryInputProcessor = new MemoryInputProcessor($firstMemoryProvider);

        $memoryInputProcessor->processInput($input = new Input(
            self::createStub(Model::class),
            new MessageBag(),
            []
        ));

        self::assertArrayNotHasKey('use_memory', $input->getOptions());
        self::assertSame(
            <<<MARKDOWN
                # Conversation Memory
                This is the memory I have found for this conversation. The memory has more weight to answer user input,
                so try to answer utilizing the memory as much as possible. Your answer must be changed to fit the given
                memory. If the memory is irrelevant, ignore it. Do not reply to the this section of the prompt and do not
                reference it as this is just for your reference.

                First memory content
                MARKDOWN,
            $input->messages->getSystemMessage()->content,
        );
    }

    #[Test]
    public function itIsAddingMultipleMemoryFromSingleProviderToSystemPrompt(): void
    {
        $firstMemoryProvider = $this->createMock(MemoryProviderInterface::class);
        $firstMemoryProvider->expects($this->once())
            ->method('loadMemory')
            ->willReturn([new Memory('First memory content'), new Memory('Second memory content')]);

        $memoryInputProcessor = new MemoryInputProcessor($firstMemoryProvider);

        $memoryInputProcessor->processInput($input = new Input(
            self::createStub(Model::class),
            new MessageBag(),
            []
        ));

        self::assertArrayNotHasKey('use_memory', $input->getOptions());
        self::assertSame(
            <<<MARKDOWN
                # Conversation Memory
                This is the memory I have found for this conversation. The memory has more weight to answer user input,
                so try to answer utilizing the memory as much as possible. Your answer must be changed to fit the given
                memory. If the memory is irrelevant, ignore it. Do not reply to the this section of the prompt and do not
                reference it as this is just for your reference.

                First memory content
                Second memory content
                MARKDOWN,
            $input->messages->getSystemMessage()->content,
        );
    }

    #[Test]
    public function itIsNotAddingAnythingIfMemoryWasEmpty(): void
    {
        $firstMemoryProvider = $this->createMock(MemoryProviderInterface::class);
        $firstMemoryProvider->expects($this->once())
            ->method('loadMemory')
            ->willReturn([]);

        $memoryInputProcessor = new MemoryInputProcessor($firstMemoryProvider);

        $memoryInputProcessor->processInput($input = new Input(
            self::createStub(Model::class),
            new MessageBag(),
            []
        ));

        self::assertArrayNotHasKey('use_memory', $input->getOptions());
        self::assertNull($input->messages->getSystemMessage()?->content);
    }
}
