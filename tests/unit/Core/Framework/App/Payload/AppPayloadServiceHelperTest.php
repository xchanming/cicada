<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Payload;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Api\Serializer\JsonEntityEncoder;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\App\Payload\SourcedPayloadInterface;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\App\TaxProvider\Payload\TaxProviderPayload;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Struct\Serializer\StructNormalizer;
use Cicada\Core\Framework\Test\Store\StaticInAppPurchaseFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\TaxProvider\TaxProviderDefinition;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * @internal
 */
#[CoversClass(AppPayloadServiceHelper::class)]
class AppPayloadServiceHelperTest extends TestCase
{
    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
    }

    public function testBuildSource(): void
    {
        $inAppPurchase = StaticInAppPurchaseFactory::createWithFeatures([
            'TestApp' => ['purchase-1', 'purchase-2'],
            'AnotherApp' => ['purchase-3'],
        ]);

        $shopIdProvider = $this->createMock(ShopIdProvider::class);
        $shopIdProvider
            ->method('getShopId')
            ->willReturn($this->ids->get('shop-id'));

        $appPayloadServiceHelper = new AppPayloadServiceHelper(
            $this->createMock(DefinitionInstanceRegistry::class),
            $this->createMock(JsonEntityEncoder::class),
            $shopIdProvider,
            $inAppPurchase,
            'https://cicada.com'
        );

        $source = $appPayloadServiceHelper->buildSource('1.0.0', 'TestApp');

        static::assertSame('https://cicada.com', $source->getUrl());
        static::assertSame($this->ids->get('shop-id'), $source->getShopId());
        static::assertSame('1.0.0', $source->getAppVersion());
        static::assertSame('a6a4063ffda65516983ad40e8dc91db6', $source->getInAppPurchases());
    }

    public function testEncode(): void
    {
        $context = new Context(new SystemSource());
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getContext')
            ->willReturn($context);

        $cart = new Cart($this->ids->get('cart'));
        $source = new Source('https://cicada.com', $this->ids->get('shop-id'), '1.0.0');
        $payload = new TaxProviderPayload($cart, $salesChannelContext);
        $payload->setSource($source);

        $definitionInstanceRegistry = $this->createMock(DefinitionInstanceRegistry::class);
        $definitionInstanceRegistry
            ->method('getByEntityClass')
            ->willReturn(new TaxProviderDefinition());

        $entityEncoder = new JsonEntityEncoder(
            new Serializer([new StructNormalizer()], [new JsonEncoder()])
        );

        $appPayloadServiceHelper = new AppPayloadServiceHelper(
            $definitionInstanceRegistry,
            $entityEncoder,
            $this->createMock(ShopIdProvider::class),
            StaticInAppPurchaseFactory::createWithFeatures(),
            'https://cicada.com'
        );

        $array = $appPayloadServiceHelper->encode($payload);

        static::assertSame(['source' => $source, 'cart' => $cart, 'context' => []], $array);
    }

    public function testCreateRequestOptionsWithNoParams(): void
    {
        $context = Context::createDefaultContext();
        $definitionInstanceRegistry = $this->createMock(DefinitionInstanceRegistry::class);
        $entityEncoder = $this->createMock(JsonEntityEncoder::class);
        $shopIdProvider = $this->createMock(ShopIdProvider::class);
        $shopIdProvider
            ->method('getShopId')
            ->willReturn($this->ids->get('shop-id'));

        $appPayloadServiceHelper = new AppPayloadServiceHelper(
            $definitionInstanceRegistry,
            $entityEncoder,
            $shopIdProvider,
            StaticInAppPurchaseFactory::createWithFeatures(),
            'https://cicada.com'
        );

        $app = new AppEntity();
        $app->setName('TestApp');
        $app->setId($this->ids->get('app'));
        $app->setVersion('1.0.0');
        $app->setAppSecret('top-secret');

        $payload = $this->createMock(SourcedPayloadInterface::class);
        $payload
            ->expects(static::once())
            ->method('setSource')
            ->with(static::isInstanceOf(Source::class));
        $payload
            ->method('jsonSerialize')
            ->willReturn(['key' => 'value']);

        $jsonPayload = $appPayloadServiceHelper->createRequestOptions($payload, $app, $context)->jsonSerialize();

        static::assertSame($context, $jsonPayload[AuthMiddleware::APP_REQUEST_CONTEXT]);
        static::assertSame('top-secret', $jsonPayload[AuthMiddleware::APP_REQUEST_TYPE][AuthMiddleware::APP_SECRET]);
        static::assertSame(['Content-Type' => 'application/json'], $jsonPayload['headers']);
        static::assertJsonStringEqualsJsonString('{"key":"value"}', $jsonPayload['body']);
    }

    public function testCreateRequestOptionsWithAdditionalParams(): void
    {
        $context = Context::createDefaultContext();
        $definitionInstanceRegistry = $this->createMock(DefinitionInstanceRegistry::class);
        $entityEncoder = $this->createMock(JsonEntityEncoder::class);
        $shopIdProvider = $this->createMock(ShopIdProvider::class);
        $shopIdProvider
            ->method('getShopId')
            ->willReturn($this->ids->get('shop-id'));

        $appPayloadServiceHelper = new AppPayloadServiceHelper(
            $definitionInstanceRegistry,
            $entityEncoder,
            $shopIdProvider,
            StaticInAppPurchaseFactory::createWithFeatures(),
            'https://cicada.com'
        );

        $app = new AppEntity();
        $app->setName('TestApp');
        $app->setId($this->ids->get('app'));
        $app->setVersion('1.0.0');
        $app->setAppSecret('top-secret');

        $payload = $this->createMock(SourcedPayloadInterface::class);
        $payload
            ->expects(static::once())
            ->method('setSource')
            ->with(static::isInstanceOf(Source::class));
        $payload
            ->method('jsonSerialize')
            ->willReturn(['key' => 'value']);

        $jsonPayload = $appPayloadServiceHelper->createRequestOptions($payload, $app, $context, ['timeout' => 50])->jsonSerialize();

        static::assertSame($context, $jsonPayload[AuthMiddleware::APP_REQUEST_CONTEXT]);
        static::assertSame('top-secret', $jsonPayload[AuthMiddleware::APP_REQUEST_TYPE][AuthMiddleware::APP_SECRET]);
        static::assertSame(['Content-Type' => 'application/json'], $jsonPayload['headers']);
        static::assertArrayHasKey('timeout', $jsonPayload);
        static::assertSame(50, $jsonPayload['timeout']);
        static::assertJsonStringEqualsJsonString('{"key":"value"}', $jsonPayload['body']);
    }

    public function testCreateRequestOptionsThrowsExceptionWhenNoAppSecret(): void
    {
        static::expectException(AppException::class);
        static::expectExceptionMessage('App registration for "TestApp" failed: App secret is missing');

        $context = Context::createDefaultContext();
        $definitionInstanceRegistry = $this->createMock(DefinitionInstanceRegistry::class);
        $entityEncoder = $this->createMock(JsonEntityEncoder::class);
        $shopIdProvider = $this->createMock(ShopIdProvider::class);

        $appPayloadServiceHelper = new AppPayloadServiceHelper(
            $definitionInstanceRegistry,
            $entityEncoder,
            $shopIdProvider,
            StaticInAppPurchaseFactory::createWithFeatures(),
            'https://cicada.com'
        );

        $app = new AppEntity();
        $app->setName('TestApp');
        $app->setId($this->ids->get('app'));
        $app->setVersion('1.0.0');
        $app->setName('TestApp');

        $payload = $this->createMock(SourcedPayloadInterface::class);

        $appPayloadServiceHelper->createRequestOptions($payload, $app, $context);
    }
}
