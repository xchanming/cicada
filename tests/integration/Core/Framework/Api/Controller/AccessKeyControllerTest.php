<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api\Controller;

use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AccessKeyControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    public function testIntegrationAccessKey(): void
    {
        $url = '/api/_action/access-key/intergration';
        $this->getBrowser()->request('GET', $url);

        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);
        $body = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $response->getStatusCode(), print_r($body, true));
        static::assertIsArray($body);
        static::assertArrayHasKey('accessKey', $body);
        static::assertArrayHasKey('secretAccessKey', $body);
        static::assertNotEmpty($body['secretAccessKey']);
        static::assertNotEmpty($body['accessKey']);

        $e = null;

        try {
            AccessKeyHelper::getOrigin($body['accessKey']);
        } catch (\Exception $e) {
        }
        static::assertTrue($e === null);
    }

    public function testUserAccessKey(): void
    {
        $url = '/api/_action/access-key/user';
        $this->getBrowser()->request('GET', $url);

        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);
        $body = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $response->getStatusCode(), print_r($body, true));
        static::assertIsArray($body);
        static::assertArrayHasKey('accessKey', $body);
        static::assertArrayHasKey('secretAccessKey', $body);
        static::assertNotEmpty($body['secretAccessKey']);
        static::assertNotEmpty($body['accessKey']);

        $e = null;

        try {
            AccessKeyHelper::getOrigin($body['accessKey']);
        } catch (\Exception $e) {
        }
        static::assertTrue($e === null);
    }

    public function testSalesChannelAccessKey(): void
    {
        $url = '/api/_action/access-key/sales-channel';
        $this->getBrowser()->request('GET', $url);

        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);
        $body = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $response->getStatusCode(), print_r($body, true));
        static::assertIsArray($body);
        static::assertArrayHasKey('accessKey', $body);
        static::assertNotEmpty($body['accessKey']);

        $e = null;

        try {
            AccessKeyHelper::getOrigin($body['accessKey']);
        } catch (\Exception $e) {
        }
        static::assertTrue($e === null);
    }
}
