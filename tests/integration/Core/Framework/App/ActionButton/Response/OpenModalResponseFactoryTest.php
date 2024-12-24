<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\ActionButton\Response;

use Cicada\Core\Framework\App\ActionButton\AppAction;
use Cicada\Core\Framework\App\ActionButton\Response\NotificationResponse;
use Cicada\Core\Framework\App\ActionButton\Response\OpenModalResponse;
use Cicada\Core\Framework\App\ActionButton\Response\OpenModalResponseFactory;
use Cicada\Core\Framework\App\ActionButton\Response\OpenNewTabResponse;
use Cicada\Core\Framework\App\ActionButton\Response\ReloadDataResponse;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Tests\Integration\Core\Framework\App\GuzzleTestClientBehaviour;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class OpenModalResponseFactoryTest extends TestCase
{
    use GuzzleTestClientBehaviour;

    private OpenModalResponseFactory $factory;

    private AppAction $action;

    protected function setUp(): void
    {
        $this->factory = static::getContainer()->get(OpenModalResponseFactory::class);
        $app = new AppEntity();
        $app->setName('TestApp');
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
    public function testSupportsOnlyOpenModalActionType(string $actionType, bool $isSupported): void
    {
        static::assertSame($isSupported, $this->factory->supports($actionType));
    }

    public function testCreatesOpenModalResponse(): void
    {
        $response = $this->factory->create($this->action, [
            'iframeUrl' => 'http://iframe.url',
            'size' => 'medium',
            'expand' => false,
        ], Context::createDefaultContext());

        static::assertInstanceOf(OpenModalResponse::class, $response);
    }

    /**
     * @param array<bool|string> $payload
     */
    #[DataProvider('provideInvalidPayloads')]
    public function testThrowsExceptionWhenValidationFails(array $payload, string $message): void
    {
        static::expectException(AppException::class);
        static::expectExceptionMessage($message);

        $this->factory->create(
            $this->action,
            $payload,
            Context::createDefaultContext()
        );
    }

    /**
     * @return array<array<string|bool>>
     */
    public static function provideActionTypes(): array
    {
        return [
            [NotificationResponse::ACTION_TYPE, false],
            [OpenModalResponse::ACTION_TYPE, true],
            [OpenNewTabResponse::ACTION_TYPE, false],
            [ReloadDataResponse::ACTION_TYPE, false],
        ];
    }

    /**
     * @return array<array<array<bool|string>|string>>
     */
    public static function provideInvalidPayloads(): array
    {
        return [
            [
                ['size' => 'medium', 'expand' => false],
                'The app provided an invalid iframeUrl',
            ],
            [
                ['iframeUrl' => '', 'size' => 'medium', 'expand' => false],
                'The app provided an invalid iframeUrl',
            ],
            [
                ['iframeUrl' => 'http://iframe.url', 'expand' => false],
                'The app provided an invalid size',
            ],
            [
                ['iframeUrl' => 'http://iframe.url', 'size' => '', 'expand' => false],
                'The app provided an invalid size',
            ],
            [
                ['iframeUrl' => 'http://iframe.url', 'size' => 'xl', 'expand' => false],
                'The app provided an invalid size',
            ],
        ];
    }
}
