Orq.ai Platform
===============

[Orq.ai](https://orq.ai) AI gateway platform bridge for Symfony AI.

The bridge targets the OpenAI-compatible router of the gateway
(`https://api.orq.ai/v3/router`) and supports the chat completions and the embeddings
endpoints.

Usage
-----

```php
use Symfony\AI\Platform\Bridge\OrqAi\Factory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

$platform = Factory::createPlatform($_ENV['ORQ_API_KEY']);

$messages = new MessageBag(
    Message::forSystem('You are a pirate and you write funny.'),
    Message::ofUser('What is the Symfony framework?'),
);

$result = $platform->invoke('openai/gpt-4o', $messages);

echo $result->asText();
```

Models are addressed with the gateway's `provider/model` identifiers, e.g.
`openai/gpt-4o`, `anthropic/claude-sonnet-4-5` or `openai/text-embedding-3-small`.

By default any model name is accepted and routed by naming convention: an
identifier containing `embed` is treated as an embeddings model, everything else
as a completions model. Use `ModelApiCatalog` to restrict the platform to the
models actually enabled for the workspace:

```php
use Symfony\AI\Platform\Bridge\OrqAi\Factory;
use Symfony\AI\Platform\Bridge\OrqAi\ModelApiCatalog;

$platform = Factory::createPlatform(
    $_ENV['ORQ_API_KEY'],
    modelCatalog: new ModelApiCatalog($httpClient, $_ENV['ORQ_API_KEY']),
);
```

The `/models` endpoint reports no modality, so the mapping is name-based on both catalogs.
Models that are neither chat nor embedding ones, such as the moderation, OCR and audio ones,
are therefore reported as completions models; those endpoints are out of the scope of this
bridge. Embedding models whose identifier does not contain `embed`, such as
`jina/jina-clip-v2` or `scaleway/bge-multilingual-gemma2`, are misread the same way: register
them explicitly to reach the embeddings endpoint:

```php
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Bridge\Generic\ModelCatalog;
use Symfony\AI\Platform\Capability;

$platform = Factory::createPlatform($_ENV['ORQ_API_KEY'], modelCatalog: new ModelCatalog([
    'jina/jina-clip-v2' => [
        'class' => EmbeddingsModel::class,
        'capabilities' => [Capability::INPUT_TEXT, Capability::EMBEDDINGS],
    ],
]));
```

`/models` reports the models enabled for the workspace, so `ModelApiCatalog` fails fast on a
model nobody enabled instead of letting the call reach the gateway, which is what the AI Bundle
configures. The default catalog accepts any identifier and leaves that validation to the router.

Gateway features such as retries, caching or load balancing are request body
fields of the router, so they are passed through as regular invocation options:

```php
$result = $platform->invoke('openai/gpt-4o', $messages, [
    'retry' => ['count' => 3, 'on_codes' => [429, 500, 502, 503, 504]],
    'cache' => ['type' => 'exact_match', 'ttl' => 3600],
    'load_balancer' => [
        'type' => 'weight_based',
        'models' => [
            ['model' => 'openai/gpt-4o', 'weight' => 0.7],
            ['model' => 'anthropic/claude-haiku-4-5', 'weight' => 0.3],
        ],
    ],
]);
```

Tool Calling
------------

Depending on the upstream provider, the router reports `finish_reason: "stop"` even when the
model asked for a tool call, instead of the `"tool_calls"` value the OpenAI schema defines:
Google does, Mistral does not. The bridge keys the conversion on the presence of the tool
calls themselves, so tool calling works on both the buffered and the streamed path. Only the
buffered result normalizes the finish reason to a tool call; a streamed one still reports the
reason the provider sent.

Running a full agent loop is another matter: some reasoning models, Gemini 3 among them,
require a thought signature on the follow-up request to continue a tool-calling conversation.
The router documents a `thought_signature` field on the tool calls of its chat completions
schema, but does not populate it in practice, so the bridge has nothing to send back and the
second round trip is rejected upstream. Pick a model from another provider for agents that
call tools.

Orq.ai Documentation
--------------------

 * [Docs index](https://docs.orq.ai/)
 * [AI gateway introduction](https://docs.orq.ai/docs/ai-gateway/get-started/introduction)
 * [OpenAI-compatible API](https://docs.orq.ai/docs/ai-gateway/features/openai-compatible-api)
 * [API keys](https://docs.orq.ai/docs/ai-gateway/configuration/api-keys)

Resources
---------

 * [Contributing](https://symfony.com/doc/current/contributing/index.html)
 * [Report issues](https://github.com/symfony/ai/issues) and
   [send Pull Requests](https://github.com/symfony/ai/pulls)
   in the [main Symfony AI repository](https://github.com/symfony/ai)
