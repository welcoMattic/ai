<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\OpenAi\Factory;
use Symfony\AI\Store\Document\Loader\RssFeedLoader;
use Symfony\AI\Store\Document\Transformer\TextSplitTransformer;
use Symfony\AI\Store\Document\Vectorizer;
use Symfony\AI\Store\Indexer\DocumentProcessor;
use Symfony\AI\Store\Indexer\SourceIndexer;
use Symfony\AI\Store\InMemory\Store as InMemoryStore;
use Symfony\AI\Store\Query\VectorQuery;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = Factory::createPlatform(env('OPENAI_API_KEY'), http_client());
$store = new InMemoryStore();
$vectorizer = new Vectorizer($platform, 'text-embedding-3-small?dimensions=256');
$indexer = new SourceIndexer(
    loader: new RssFeedLoader(http_client()),
    processor: new DocumentProcessor(
        vectorizer: $vectorizer,
        store: $store,
        transformers: [
            new TextSplitTransformer(chunkSize: 2000, overlap: 200),
        ],
        logger: logger(),
    ),
);

$indexer->index([
    'https://feeds.feedburner.com/symfony/blog',
]);

$vector = $vectorizer->vectorize('Week of Symfony');
$results = $store->query(new VectorQuery($vector));
foreach ($results as $i => $document) {
    echo sprintf("%d. %s\n", $i + 1, $document->getMetadata()['title']);
}
