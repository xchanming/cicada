<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\InAppPurchases\Api;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\ShopApiSource;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\InAppPurchase\Api\InAppPurchasesController;
use Cicada\Core\Framework\Store\StoreException;
use Cicada\Core\Framework\Test\Store\StaticInAppPurchaseFactory;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(InAppPurchasesController::class)]
class InAppPurchasesControllerTest extends TestCase
{
    private Context $context;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext(new AdminApiSource('test-user', 'test-extension'));
    }

    public function testActiveInAppPurchasesWithIncorrectContext(): void
    {
        static::expectException(StoreException::class);
        static::expectExceptionMessage('Expected context source to be "Cicada\Core\Framework\Api\Context\AdminApiSource" but got "Cicada\Core\Framework\Api\Context\ShopApiSource".');

        $this->createController()->activeExtensionInAppPurchases(
            Context::createDefaultContext(new ShopApiSource('test-channel'))
        );
    }

    public function testActiveInAppPurchasesWithNoIntegrationId(): void
    {
        static::expectException(StoreException::class);
        static::expectExceptionMessage('No integration available in context source "Cicada\Core\Framework\Api\Context\AdminApiSource"');

        $this->createController()->activeExtensionInAppPurchases(
            $this->context = Context::createDefaultContext(new AdminApiSource('test-user'))
        );
    }

    public function testActiveInAppPurchasesWithNoPurchasesShouldReturnEmptyArray(): void
    {
        $response = $this->createController()->activeExtensionInAppPurchases($this->context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        static::assertIsString($content);
        static::assertSame(
            ['inAppPurchases' => []],
            json_decode($content, true, 512, \JSON_THROW_ON_ERROR)
        );
    }

    public function testActiveInAppPurchasesWithPurchasesShouldReturnArrayWithApps(): void
    {
        $controller = $this->createController(['test-extension' => ['purchase1', 'purchase2']]);

        $response = $controller->activeExtensionInAppPurchases($this->context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        static::assertIsString($content);
        static::assertSame(
            ['inAppPurchases' => ['purchase1', 'purchase2']],
            json_decode($content, true, 512, \JSON_THROW_ON_ERROR)
        );

        $controller = $this->createController(['test-extension' => ['purchase1'], 'anotherExtension' => ['purchase2']]);

        $response = $controller->activeExtensionInAppPurchases($this->context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        static::assertIsString($content);
        static::assertSame(
            ['inAppPurchases' => ['purchase1']],
            json_decode($content, true, 512, \JSON_THROW_ON_ERROR)
        );
    }

    public function testCheckInAppPurchaseActiveWithoutRequiredParameterThrowsError(): void
    {
        static::expectException(StoreException::class);
        static::expectExceptionMessage('Parameter "identifier" is missing.');

        $request = new RequestDataBag();

        $this->createController()->checkExtensionInAppPurchaseIsActive($request, $this->context);
    }

    public function testCheckInAppPurchaseActiveWithNonPurchasedAppReturnsFalse(): void
    {
        $request = new RequestDataBag();
        $request->set('identifier', 'nonPurchasedApp');

        $response = $this->createController()->checkExtensionInAppPurchaseIsActive($request, $this->context);
        static::assertIsString($response->getContent());
        static::assertSame(
            ['isActive' => false],
            json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR)
        );
    }

    public function testCheckInAppPurchaseActiveWithPurchasedAppReturnsTrue(): void
    {
        $request = new RequestDataBag();
        $request->set('identifier', 'purchase1');

        $controller = $this->createController(['test-extension' => ['purchase1', 'purchase2']]);

        $response = $controller->checkExtensionInAppPurchaseIsActive($request, $this->context);
        static::assertIsString($response->getContent());
        static::assertSame(
            ['isActive' => true],
            json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR)
        );

        $request->set('identifier', 'purchase2');
        $response = $controller->checkExtensionInAppPurchaseIsActive($request, $this->context);
        static::assertIsString($response->getContent());
        static::assertSame(
            ['isActive' => true],
            json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @param array<string, array<int, string>> $purchases
     */
    private function createController(array $purchases = []): InAppPurchasesController
    {
        $app = new AppEntity();
        $app->setId(Uuid::randomHex());
        $app->setName('test-extension');
        /** @var StaticEntityRepository<AppCollection> $repository */
        $repository = new StaticEntityRepository([new AppCollection([$app]), new AppCollection([$app])]);

        return new InAppPurchasesController(
            StaticInAppPurchaseFactory::createWithFeatures($purchases),
            $repository,
        );
    }
}
