<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Shipping\Validator;

use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Checkout\Shipping\Validator\ShippingMethodValidator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Cicada\Core\Test\Stub\Doctrine\FakeConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShippingMethodValidator::class)]
class ShippingMethodValidatorTest extends TestCase
{
    private WriteContext $context;

    private ShippingMethodDefinition $shippingMethodDefinition;

    private PaymentMethodDefinition $paymentMethodDefinition;

    protected function setUp(): void
    {
        $this->context = WriteContext::createFromContext(Context::createDefaultContext());

        $registry = new StaticDefinitionInstanceRegistry(
            [ShippingMethodDefinition::class, PaymentMethodDefinition::class],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        $definition = $registry->get(ShippingMethodDefinition::class);
        static::assertInstanceOf(ShippingMethodDefinition::class, $definition);
        $this->shippingMethodDefinition = $definition;

        $definition = $registry->get(PaymentMethodDefinition::class);
        static::assertInstanceOf(PaymentMethodDefinition::class, $definition);
        $this->paymentMethodDefinition = $definition;
    }

    public function testSubscribedEvents(): void
    {
        $events = ShippingMethodValidator::getSubscribedEvents();

        static::assertCount(1, $events);
        static::assertEquals('preValidate', $events[PreWriteValidationEvent::class]);
    }

    public function testPreValidateWithInvalidCommands(): void
    {
        $commands = [];
        $commands[] = new UpdateCommand($this->paymentMethodDefinition, [], ['id' => Uuid::randomBytes()], EntityExistence::createForEntity('shipping_method', ['id' => Uuid::randomBytes()]), '/0/');
        $commands[] = new class($this->shippingMethodDefinition, [], [], EntityExistence::createForEntity('shipping_method', ['id' => Uuid::randomBytes()]), '/0/') extends WriteCommand {
            public function getPrivilege(): ?string
            {
                return null;
            }
        };

        $fakeConnection = new FakeConnection([]);

        $event = new PreWriteValidationEvent($this->context, $commands);
        $validator = new ShippingMethodValidator($fakeConnection);
        $validator->preValidate($event);

        static::assertCount(0, $event->getExceptions()->getExceptions());
    }

    #[DataProvider('shippingMethodTaxProvider')]
    public function testShippingMethodValidator(?string $taxType, ?string $taxId, bool $success): void
    {
        $commands = [];
        $commands[] = new InsertCommand(
            $this->shippingMethodDefinition,
            [
                'name' => 'test',
                'tax_type' => $taxType,
                'tax_id' => $taxId,
                'availability_rule' => [
                    'id' => Uuid::randomBytes(),
                    'name' => 'asd',
                    'priority' => 2,
                ],
            ],
            ['id' => Uuid::randomBytes()],
            $this->createMock(EntityExistence::class),
            '/0/'
        );

        $fakeConnection = new FakeConnection([]);

        $event = new PreWriteValidationEvent($this->context, $commands);
        $validator = new ShippingMethodValidator($fakeConnection);
        $validator->preValidate($event);

        $exception = null;

        try {
            $event->getExceptions()->tryToThrow();
        } catch (WriteException $e) {
            $exception = $e;
        }

        if (!$success) {
            static::assertNotNull($exception);
            static::assertEquals(WriteConstraintViolationException::class, $exception->getExceptions()[0]::class);
        } else {
            static::assertNull($exception);
        }
    }

    public static function shippingMethodTaxProvider(): \Generator
    {
        yield 'Test tax type is null' => [null, null, true];
        yield 'Test tax type is invalid' => ['invalid', null, false];
        yield 'Test tax type is auto' => ['auto', null, true];
        yield 'Test tax type is highest' => ['highest', null, true];
        yield 'Test tax type is fixed without tax ID' => ['fixed', null, false];
        yield 'Test tax type is fixed with tax ID' => ['fixed', Uuid::randomBytes(), true];
    }
}
