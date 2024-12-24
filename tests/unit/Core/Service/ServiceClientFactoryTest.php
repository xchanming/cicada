<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Service;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Service\ServiceClientFactory;
use Cicada\Core\Service\ServiceException;
use Cicada\Core\Service\ServiceRegistryClient;
use Cicada\Core\Service\ServiceRegistryEntry;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(ServiceClientFactory::class)]
class ServiceClientFactoryTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;

    private HttpClientInterface&MockObject $scopedClient;

    private AuthMiddleware&MockObject $authMiddleware;

    private AppPayloadServiceHelper&MockObject $appPayloadServiceHelper;

    protected function setUp(): void
    {
        $this->scopedClient = $this->createMock(HttpClientInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->authMiddleware = $this->createMock(AuthMiddleware::class);
        $this->appPayloadServiceHelper = $this->createMock(AppPayloadServiceHelper::class);
    }

    public function testNewForServiceRegistryEntry(): void
    {
        $this->httpClient
            ->expects(static::once())
            ->method('withOptions')
            ->with([
                'base_uri' => 'https://mycoolservice.com',
            ])
            ->willReturn($this->scopedClient);

        $serviceClientRegistry = static::createMock(ServiceRegistryClient::class);

        $clientFactory = new ServiceClientFactory($this->httpClient, $serviceClientRegistry, '6.6.0.0', $this->authMiddleware, $this->appPayloadServiceHelper);
        $client = $clientFactory->newFor(new ServiceRegistryEntry('MyCoolService', 'My Cool Service', 'https://mycoolservice.com', '/app-endpoint'));

        static::assertSame($this->scopedClient, $client->client);
    }

    public function testFromNameProxiesToServiceRegistryClient(): void
    {
        $this->httpClient
            ->expects(static::once())
            ->method('withOptions')
            ->with([
                'base_uri' => 'https://mycoolservice.com',
            ])
            ->willReturn($this->scopedClient);
        $serviceClientRegistry = static::createMock(ServiceRegistryClient::class);
        $serviceClientRegistry->expects(static::once())
            ->method('get')
            ->with('MyCoolService')
            ->willReturn(new ServiceRegistryEntry('MyCoolService', 'My Cool Service', 'https://mycoolservice.com', '/app-endpoint'));

        $clientFactory = new ServiceClientFactory($this->httpClient, $serviceClientRegistry, '6.6.0.0', $this->authMiddleware, $this->appPayloadServiceHelper);
        $client = $clientFactory->fromName('MyCoolService');

        static::assertSame($this->scopedClient, $client->client);
    }

    public function testCreateAuthenticatedClient(): void
    {
        $entry = new ServiceRegistryEntry('serviceA', 'description', 'https://example.com', 'appEndpoint', true, 'licenseSyncEndPoint');
        $app = new AppEntity();
        $app->setId(Uuid::randomHex());
        $app->setSelfManaged(true);
        $app->setAppSecret('app_secret');
        $app->setVersion('1.0.0');
        $app->setName('TestApp');
        $context = Context::createDefaultContext();

        $serviceClientRegistry = static::createMock(ServiceRegistryClient::class);
        $clientFactory = new ServiceClientFactory($this->httpClient, $serviceClientRegistry, '6.6.0.0', $this->authMiddleware, $this->appPayloadServiceHelper);
        $serviceAuthedClient = $clientFactory->newAuthenticatedFor($entry, $app, $context);
        $config = $this->getConfigFromClient($serviceAuthedClient->client);

        static::assertArrayHasKey('headers', $config);
        $headers = $config['headers'];

        static::assertIsArray($headers);
        static::assertArrayHasKey('Content-Type', $headers);
        static::assertEquals('application/json', $headers['Content-Type']);
        static::assertEquals($context, $config[AuthMiddleware::APP_REQUEST_CONTEXT]);
        static::assertArrayHasKey('base_uri', $config);
        static::assertEquals('https://example.com', $config['base_uri']);
    }

    public function testAuthenticatedClientThrowsExceptionWhenAppSecretNull(): void
    {
        $entry = new ServiceRegistryEntry('serviceA', 'description', 'https://example.com', 'appEndpoint', true, 'licenseSyncEndPoint');
        $app = new AppEntity();
        $app->setId(Uuid::randomHex());
        $app->setSelfManaged(true);
        $app->setAppSecret(null);
        $app->setVersion('1.0.0');

        $context = Context::createDefaultContext();

        $this->expectException(ServiceException::class);
        $serviceClientRegistry = static::createMock(ServiceRegistryClient::class);
        $clientFactory = new ServiceClientFactory($this->httpClient, $serviceClientRegistry, '6.6.0.0', $this->authMiddleware, $this->appPayloadServiceHelper);
        $clientFactory->newAuthenticatedFor($entry, $app, $context);
    }

    public function testAuthenticatedClientThrowsAppUrlChangeDetectedException(): void
    {
        $entry = new ServiceRegistryEntry('serviceA', 'description', 'https://example.com', 'appEndpoint', true, 'licenseSyncEndPoint');
        $app = new AppEntity();
        $app->setId(Uuid::randomHex());
        $app->setSelfManaged(true);
        $app->setAppSecret('app_secret');
        $app->setVersion('1.0.0');
        $app->setName('TestApp');

        $context = Context::createDefaultContext();

        $this->appPayloadServiceHelper->method('buildSource')->willThrowException(new AppUrlChangeDetectedException('App URL changed', 'foo', 'shopid'));

        $this->expectException(AppUrlChangeDetectedException::class);
        $serviceClientRegistry = static::createMock(ServiceRegistryClient::class);
        $clientFactory = new ServiceClientFactory($this->httpClient, $serviceClientRegistry, '6.6.0.0', $this->authMiddleware, $this->appPayloadServiceHelper);
        $clientFactory->newAuthenticatedFor($entry, $app, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfigFromClient(ClientInterface $client): array
    {
        $reflObject = new \ReflectionObject($client);
        $reflProp = $reflObject->getProperty('config');

        $reflProp->setAccessible(true);

        return $reflProp->getValue($client);
    }
}
