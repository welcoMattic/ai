<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Blade\Tests\ImageGeneration;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Blade\ImageGeneration;
use Symfony\AI\Platform\Bridge\Blade\ImageGeneration\ResultConverter;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Exception\ModelNotFoundException;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Result\BinaryResult;
use Symfony\AI\Platform\Result\MultiPartResult;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ResultConverterTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGD4DwABBAEAHnOcQAAAAABJRU5ErkJggg==';

    public function testItSupportsTheImageGenerationModelOnly()
    {
        $converter = new ResultConverter();

        $this->assertTrue($converter->supports(new ImageGeneration('flux-2-klein')));
        $this->assertFalse($converter->supports(new CompletionsModel('voxtral-mini-3b')));
    }

    public function testItConvertsASingleImageToABinaryResult()
    {
        $result = $this->convert(['created' => 1792744675, 'model' => 'flux-2-klein', 'data' => [['b64_json' => self::PNG]]]);

        $this->assertInstanceOf(BinaryResult::class, $result);
        $this->assertSame(base64_decode(self::PNG), $result->getContent());
        $this->assertSame('image/png', $result->getMimeType());
    }

    public function testItConvertsSeveralImagesToAMultiPartResult()
    {
        $result = $this->convert(['data' => [['b64_json' => self::PNG], ['b64_json' => self::PNG]]]);

        $this->assertInstanceOf(MultiPartResult::class, $result);
        $this->assertCount(2, $result->getContent());
        $this->assertContainsOnlyInstancesOf(BinaryResult::class, $result->getContent());
    }

    public function testItFailsWithoutImage()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No image generated.');

        $this->convert(['created' => 1792744675, 'data' => []]);
    }

    public function testItFailsWithoutBase64Payload()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The generated image has no "b64_json" field.');

        $this->convert(['data' => [['url' => 'https://example.com/image.png']]]);
    }

    public function testItMapsKnownHttpErrors()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage("The model 'does-not-exist' does not exist.");

        $this->convert(['error' => ['message' => "The model 'does-not-exist' does not exist.", 'type' => 'invalid_request_error', 'code' => 'model_not_found', 'param' => 'model']], 404);
    }

    public function testItFailsOnOtherHttpErrors()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error "paid_credit_required"-insufficient_quota (-): "A paid wallet is required.".');

        $this->convert(['error' => ['message' => 'A paid wallet is required.', 'type' => 'insufficient_quota', 'code' => 'paid_credit_required']], 402);
    }

    public function testItFallsBackToTheRawBodyWhenTheErrorIsNotAnEnvelope()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected response code 402: "plain failure"');

        $httpClient = new MockHttpClient(new MockResponse('plain failure', ['http_code' => 402]));

        (new ResultConverter())->convert(new RawHttpResult($httpClient->request('POST', 'https://api.models.blade.sh/v1/images/generations')));
    }

    /**
     * @param array<string, mixed> $body
     */
    private function convert(array $body, int $status = 200): mixed
    {
        $httpClient = new MockHttpClient(new JsonMockResponse($body, ['http_code' => $status]));

        return (new ResultConverter())->convert(new RawHttpResult($httpClient->request('POST', 'https://api.models.blade.sh/v1/images/generations')));
    }
}
