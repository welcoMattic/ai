Hugging Face
============

`Hugging Face Inference Providers`_ give you access to thousands of models for chat,
computer vision, audio and other tasks. The models run on Hugging Face's own inference
service or on third-party providers (e.g. Cerebras, Groq or Together).

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ai-hugging-face-platform

Setup
-----

The bridge requires a Hugging Face `access token`_. Pass it to the factory, together with
the inference provider that runs your models::

    use Symfony\AI\Platform\Bridge\HuggingFace\Factory;
    use Symfony\AI\Platform\Bridge\HuggingFace\Provider;

    // the provider defaults to Provider::HF_INFERENCE
    $platform = Factory::createPlatform('hf_your_api_key_here', Provider::CEREBRAS);

Each model supports a different set of tasks. Pass the task in the ``task`` option of
every invocation, for example ``Task::CHAT_COMPLETION``.

Tool Calling
------------

Chat completion supports tool calling when the model and the inference provider support
it. The following example uses the Agent component to call a clock tool::

    use Symfony\AI\Agent\Agent;
    use Symfony\AI\Agent\Bridge\Clock\Clock;
    use Symfony\AI\Agent\Toolbox\Toolbox;
    use Symfony\AI\Platform\Bridge\HuggingFace\Factory;
    use Symfony\AI\Platform\Bridge\HuggingFace\Provider;
    use Symfony\AI\Platform\Bridge\HuggingFace\Task;
    use Symfony\AI\Platform\Message\Message;
    use Symfony\AI\Platform\Message\MessageBag;
    use Symfony\Component\Clock\NativeClock;

    $platform = Factory::createPlatform('hf_your_api_key_here', Provider::CEREBRAS);
    $toolbox = new Toolbox([new Clock(new NativeClock())]);
    $agent = new Agent($platform, 'openai/gpt-oss-120b', toolbox: $toolbox);

    $result = $agent->call(new MessageBag(Message::ofUser('What is the current date and time?')), [
        'task' => Task::CHAT_COMPLETION,
    ]);

    echo $result->asText();

This example also requires ``symfony/ai-agent`` and ``symfony/ai-clock-tool``. The agent
sends the ``task`` option again with each follow-up request that contains tool results.

Which models support tools, and whether they accept the ``tool_choice`` option, depends on
the provider. See the Hugging Face `function calling guide`_ for details.

Calling Tools Without the Agent
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When you call the platform directly, pass ``Tool`` objects in the ``tools`` option.
If the model calls a tool, the result is a
:class:`Symfony\\AI\\Platform\\Result\\ToolCallResult` with the ID, the name and the
decoded arguments of each call.

Run only tools you know and allow. Then add ``Message::ofAssistant($result)`` and one
``Message::ofToolCall($call, $output)`` per call to the message bag, and invoke the model
again. If the model returns arguments that are not a valid JSON object, the bridge throws a
:class:`Symfony\\AI\\Platform\\Exception\\MalformedToolCallException`.

.. note::

    The bridge doesn't support streaming, so tool calls are only available in
    non-streamed results.

See the `Hugging Face tool calling example`_ for a complete agent.

.. _`Hugging Face Inference Providers`: https://huggingface.co/docs/inference-providers/index
.. _`access token`: https://huggingface.co/docs/hub/en/security-tokens
.. _`function calling guide`: https://huggingface.co/docs/inference-providers/guides/function-calling
.. _`Hugging Face tool calling example`: https://github.com/symfony/ai/blob/main/examples/huggingface/toolcall.php
