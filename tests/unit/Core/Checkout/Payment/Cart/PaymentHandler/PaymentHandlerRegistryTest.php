<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentHandlerRegistry::class)]
class PaymentHandlerRegistryTest extends TestCase
{
    /**
     * @var array<string, AbstractPaymentHandler>
     */
    private array $registeredHandlers = [];

    private readonly Connection $connection;

    private readonly IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();

        $qb
            ->method('setParameter')
            ->willReturnCallback(function (string $key, string $paymentMethodId): QueryBuilder {
                static::assertSame('paymentMethodId', $key);

                if (\array_key_exists($paymentMethodId, $this->registeredHandlers)) {
                    $handler = $this->registeredHandlers[$paymentMethodId];

                    $result = $this->createMock(Result::class);
                    $result
                        ->method('fetchAssociative')
                        ->willReturn(['handler_identifier' => $handler::class]);
                } else {
                    $result = $this->createMock(Result::class);
                    $result
                        ->method('fetchAssociative')
                        ->willReturn(false);
                }

                $newQb = $this->createMock(QueryBuilder::class);
                $newQb
                    ->method('executeQuery')
                    ->willReturn($result);

                return $newQb;
            });

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->connection = $connection;
    }

    public function testPaymentRegistry(): void
    {
        $registry = new PaymentHandlerRegistry(
            $this->registerHandler(AbstractPaymentHandler::class),
            $this->connection,
        );

        $abstract = $registry->getPaymentMethodHandler($this->ids->get(AbstractPaymentHandler::class));
        static::assertInstanceOf(AbstractPaymentHandler::class, $abstract);

        $foo = $registry->getPaymentMethodHandler(Uuid::randomHex());
        static::assertNull($foo);
    }

    public function testRegistryWithNonPaymentInterfaceService(): void
    {
        $registry = new PaymentHandlerRegistry(
            new ServiceLocator([
                AbstractPaymentHandler::class => fn () => new class {
                },
            ]),
            $this->connection,
        );

        $handler = $registry->getPaymentMethodHandler($this->ids->get(AbstractPaymentHandler::class));
        static::assertNull($handler);
    }

    public function testRegistryWithNonRegisteredPaymentHandler(): void
    {
        $this->registerHandler(AbstractPaymentHandler::class);

        $registry = new PaymentHandlerRegistry(
            new ServiceLocator([]),
            $this->connection,
        );

        $sync = $registry->getPaymentMethodHandler($this->ids->get(AbstractPaymentHandler::class));
        static::assertNull($sync);
    }

    /**
     * @param class-string<AbstractPaymentHandler> $handler
     *
     * @return ServiceLocator<AbstractPaymentHandler>
     */
    private function registerHandler(string $handler): ServiceLocator
    {
        $class = new class extends AbstractPaymentHandler {
            public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
            {
                return false;
            }

            public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
            {
                return null;
            }
        };

        $this->registeredHandlers[Uuid::fromHexToBytes($this->ids->get($handler))] = $class;

        return new ServiceLocator([$class::class => fn () => $class]);
    }
}
