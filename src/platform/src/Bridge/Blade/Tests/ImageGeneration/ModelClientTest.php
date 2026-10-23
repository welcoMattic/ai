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
use Symfony\AI\Platform\Bridge\Blade\ImageGeneration\ModelClient;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Exception\InvalidArgumentException;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class ModelClientTest extends TestCase
{
    public function testItSupportsTheImageGenerationModelOnly()
    {
        $client = new ModelClient(new MockHttpClient(), 'https://api.models.blade.sh', 'test-key');

        $this->assertTrue($client->supports(new ImageGeneration('flux-2-klein')));
        $this->assertFalse($client->supports(new CompletionsModel('voxtral-mini-3b')));
    }

    public function testItPostsThePromptWithTheOptions()
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): JsonMockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://api.models.blade.sh/v1/images/generations', $url);
            self::assertContains('Authorization: Bearer test-key', $options['headers']);
            self::assertSame(['n' => 1, 'size' => '512x512', 'model' => 'flux-2-klein', 'prompt' => 'A red square'], json_decode($options['body'], true));

            return new JsonMockResponse(['created' => 1792744675, 'data' => []]);
        });

        $client = new ModelClient($httpClient, 'https://api.models.blade.sh/', 'test-key');
        $result = $client->request(new ImageGeneration('flux-2-klein'), 'A red square', ['n' => 1, 'size' => '512x512']);

        $this->assertInstanceOf(RawHttpResult::class, $result);
        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testItRejectsANonStringPrompt()
    {
        $this->expectException(InvalidArgumentException::class);

        (new ModelClient(new MockHttpClient(), 'https://api.models.blade.sh', 'test-key'))->request(new ImageGeneration('flux-2-klein'), ['prompt' => 'A red square']);
    }
}
