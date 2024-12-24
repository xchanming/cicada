<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\ActionButton\Response;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\ActionButton\AppAction;
use Cicada\Core\Framework\App\ActionButton\Response\NotificationResponse;
use Cicada\Core\Framework\App\ActionButton\Response\OpenModalResponse;
use Cicada\Core\Framework\App\ActionButton\Response\OpenNewTabResponse;
use Cicada\Core\Framework\App\ActionButton\Response\ReloadDataResponse;
use Cicada\Core\Framework\App\ActionButton\Response\ReloadDataResponseFactory;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Tests\Integration\Core\Framework\App\GuzzleTestClientBehaviour;

/**
 * @internal
 */
class ReloadDataResponseFactoryTest extends TestCase
{
    use GuzzleTestClientBehaviour;

    private ReloadDataResponseFactory $factory;

    private AppAction $action;

    protected function setUp(): void
    {
        $this->factory = static::getContainer()->get(ReloadDataResponseFactory::class);
        $app = new AppEntity();
        $app->setId(Uuid::randomHex());
        $app->setAppSecret('app-secret');
        $this->action = new AppAction(
            $app,
            new Source('http://shop.url', 'shop-id', '1.0.0'),
            'http://target.url',
            'customer',
            'action-name',
            [Uuid::randomHex(), Uuid::randomHex()],
            'action-it'
        );
    }

    #[DataProvider('provideActionTypes')]
    public function testSupportsOnlyReloadDataActionType(string $actionType, bool $isSupported): void
    {
        static::assertSame($isSupported, $this->factory->supports($actionType));
    }

    public function testCreatesReloadDataResponse(): void
    {
        $response = $this->factory->create($this->action, [], Context::createDefaultContext());

        static::assertInstanceOf(ReloadDataResponse::class, $response);
    }

    /**
     * @return array<int, array<string|bool>>
     */
    public static function provideActionTypes(): array
    {
        return [
            [NotificationResponse::ACTION_TYPE, false],
            [OpenModalResponse::ACTION_TYPE, false],
            [OpenNewTabResponse::ACTION_TYPE, false],
            [ReloadDataResponse::ACTION_TYPE, true],
        ];
    }
}
