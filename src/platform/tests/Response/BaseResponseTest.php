<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Tests\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Response\BaseResponse;
use Symfony\AI\Platform\Response\Exception\RawResponseAlreadySetException;
use Symfony\AI\Platform\Response\Metadata\Metadata;
use Symfony\AI\Platform\Response\Metadata\MetadataAwareTrait;
use Symfony\AI\Platform\Response\RawResponseAwareTrait;
use Symfony\AI\Platform\Response\RawResponseInterface;

#[CoversClass(BaseResponse::class)]
#[UsesTrait(MetadataAwareTrait::class)]
#[UsesTrait(RawResponseAwareTrait::class)]
#[UsesClass(Metadata::class)]
#[UsesClass(RawResponseAlreadySetException::class)]
#[Small]
final class BaseResponseTest extends TestCase
{
    #[Test]
    public function itCanHandleMetadata(): void
    {
        $response = $this->createResponse();
        $metadata = $response->getMetadata();

        self::assertCount(0, $metadata);

        $metadata->add('key', 'value');
        $metadata = $response->getMetadata();

        self::assertCount(1, $metadata);
    }

    #[Test]
    public function itCanBeEnrichedWithARawResponse(): void
    {
        $response = $this->createResponse();
        $rawResponse = $this->createRawResponse();

        $response->setRawResponse($rawResponse);
        self::assertSame($rawResponse, $response->getRawResponse());
    }

    #[Test]
    public function itThrowsAnExceptionWhenSettingARawResponseTwice(): void
    {
        self::expectException(RawResponseAlreadySetException::class);

        $response = $this->createResponse();
        $rawResponse = $this->createRawResponse();

        $response->setRawResponse($rawResponse);
        $response->setRawResponse($rawResponse);
    }

    private function createResponse(): BaseResponse
    {
        return new class extends BaseResponse {
            public function getContent(): string
            {
                return 'test';
            }
        };
    }

    public function createRawResponse(): RawResponseInterface
    {
        return new class implements RawResponseInterface {
            public function getRawData(): array
            {
                return ['key' => 'value'];
            }

            public function getRawObject(): object
            {
                return new \stdClass();
            }
        };
    }
}
