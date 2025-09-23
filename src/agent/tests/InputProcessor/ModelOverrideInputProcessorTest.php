<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Tests\InputProcessor;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\Exception\InvalidArgumentException;
use Symfony\AI\Agent\Input;
use Symfony\AI\Agent\InputProcessor\ModelOverrideInputProcessor;
use Symfony\AI\Platform\Bridge\Anthropic\Claude;
use Symfony\AI\Platform\Bridge\OpenAi\Gpt;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Model;

final class ModelOverrideInputProcessorTest extends TestCase
{
    public function testProcessInputWithValidModelOption()
    {
        $gpt = new Gpt(Gpt::GPT_4O);
        $claude = new Claude(Claude::SONNET_37);
        $input = new Input($gpt, new MessageBag(), ['model' => $claude]);

        $processor = new ModelOverrideInputProcessor();
        $processor->processInput($input);

        $this->assertSame($claude, $input->model);
    }

    public function testProcessInputWithoutModelOption()
    {
        $gpt = new Gpt(Gpt::GPT_4O);
        $input = new Input($gpt, new MessageBag());

        $processor = new ModelOverrideInputProcessor();
        $processor->processInput($input);

        $this->assertSame($gpt, $input->model);
    }

    public function testProcessInputWithInvalidModelOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "model" must be an instance of "'.Model::class.'".');

        $gpt = new Gpt(Gpt::GPT_4O);
        $model = new MessageBag();
        $input = new Input($gpt, new MessageBag(), ['model' => $model]);

        $processor = new ModelOverrideInputProcessor();
        $processor->processInput($input);
    }
}
