<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Tests\Toolbox\Tool;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\Toolbox\Tool\SimilaritySearch;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\ResultPromise;
use Symfony\AI\Platform\Result\VectorResult;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\VectorStoreInterface;
use Symfony\Component\Uid\Uuid;

#[CoversClass(SimilaritySearch::class)]
final class SimilaritySearchTest extends TestCase
{
    #[Test]
    public function searchWithResults(): void
    {
        $searchTerm = 'find similar documents';
        $vector = new Vector([0.1, 0.2, 0.3]);

        $document1 = new VectorDocument(
            Uuid::v4(),
            $vector,
            new Metadata(['title' => 'Document 1', 'content' => 'First document content']),
        );
        $document2 = new VectorDocument(
            Uuid::v4(),
            $vector,
            new Metadata(['title' => 'Document 2', 'content' => 'Second document content']),
        );

        $rawResult = $this->createMock(RawResultInterface::class);
        $vectorResult = new VectorResult($vector);
        $resultPromise = new ResultPromise(
            fn () => $vectorResult,
            $rawResult
        );

        $platform = $this->createMock(PlatformInterface::class);
        $platform->expects($this->once())
            ->method('invoke')
            ->with($this->isInstanceOf(Model::class), $searchTerm)
            ->willReturn($resultPromise);

        $vectorStore = $this->createMock(VectorStoreInterface::class);
        $vectorStore->expects($this->once())
            ->method('query')
            ->with($vector)
            ->willReturn([$document1, $document2]);

        $model = new Model('test-model');
        $similaritySearch = new SimilaritySearch($platform, $model, $vectorStore);

        $result = $similaritySearch($searchTerm);

        $this->assertSame('Found documents with following information:'.\PHP_EOL.'{"title":"Document 1","content":"First document content"}{"title":"Document 2","content":"Second document content"}', $result);
        $this->assertSame([$document1, $document2], $similaritySearch->usedDocuments);
    }

    #[Test]
    public function searchWithoutResults(): void
    {
        $searchTerm = 'find nothing';
        $vector = new Vector([0.1, 0.2, 0.3]);

        $rawResult = $this->createMock(RawResultInterface::class);
        $vectorResult = new VectorResult($vector);
        $resultPromise = new ResultPromise(
            fn () => $vectorResult,
            $rawResult
        );

        $platform = $this->createMock(PlatformInterface::class);
        $platform->expects($this->once())
            ->method('invoke')
            ->with($this->isInstanceOf(Model::class), $searchTerm)
            ->willReturn($resultPromise);

        $vectorStore = $this->createMock(VectorStoreInterface::class);
        $vectorStore->expects($this->once())
            ->method('query')
            ->with($vector)
            ->willReturn([]);

        $model = new Model('test-model');
        $similaritySearch = new SimilaritySearch($platform, $model, $vectorStore);

        $result = $similaritySearch($searchTerm);

        $this->assertSame('No results found', $result);
        $this->assertSame([], $similaritySearch->usedDocuments);
    }

    #[Test]
    public function searchWithSingleResult(): void
    {
        $searchTerm = 'specific query';
        $vector = new Vector([0.5, 0.6, 0.7]);

        $document = new VectorDocument(
            Uuid::v4(),
            $vector,
            new Metadata(['title' => 'Single Document', 'description' => 'Only one match']),
        );

        $rawResult = $this->createMock(RawResultInterface::class);
        $vectorResult = new VectorResult($vector);
        $resultPromise = new ResultPromise(
            fn () => $vectorResult,
            $rawResult
        );

        $platform = $this->createMock(PlatformInterface::class);
        $platform->expects($this->once())
            ->method('invoke')
            ->with($this->isInstanceOf(Model::class), $searchTerm)
            ->willReturn($resultPromise);

        $vectorStore = $this->createMock(VectorStoreInterface::class);
        $vectorStore->expects($this->once())
            ->method('query')
            ->with($vector)
            ->willReturn([$document]);

        $model = new Model('test-model');
        $similaritySearch = new SimilaritySearch($platform, $model, $vectorStore);

        $result = $similaritySearch($searchTerm);

        $this->assertSame('Found documents with following information:'.\PHP_EOL.'{"title":"Single Document","description":"Only one match"}', $result);
        $this->assertSame([$document], $similaritySearch->usedDocuments);
    }
}
