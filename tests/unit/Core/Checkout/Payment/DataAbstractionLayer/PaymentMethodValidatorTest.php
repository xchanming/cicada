<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\DataAbstractionLayer;

use Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodValidator;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentMethodValidator::class)]
class PaymentMethodValidatorTest extends TestCase
{
    private StaticDefinitionInstanceRegistry $definitionInstanceRegistry;

    protected function setUp(): void
    {
        $this->definitionInstanceRegistry = new StaticDefinitionInstanceRegistry(
            [PaymentMethodDefinition::class],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame(
            [
                PreWriteValidationEvent::class => 'validate',
            ],
            PaymentMethodValidator::getSubscribedEvents()
        );
    }

    public function testValidate(): void
    {
        $paymentMethodId = Uuid::randomBytes();

        $context = Context::createDefaultContext();

        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext($context),
            [new DeleteCommand(
                $this->definitionInstanceRegistry->get(PaymentMethodDefinition::class),
                ['id' => $paymentMethodId],
                new EntityExistence(PaymentMethodDefinition::ENTITY_NAME, ['id' => $paymentMethodId], true, false, false, [])
            )],
        );

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT id FROM payment_method WHERE id IN (:ids) AND plugin_id IS NOT NULL',
                ['ids' => [$paymentMethodId]],
                ['ids' => ArrayParameterType::BINARY]
            )
            ->willReturn(false);

        $subscriber = new PaymentMethodValidator($connection);
        $subscriber->validate($event);
    }

    public function testValidateWithExistingPlugin(): void
    {
        $paymentMethodId = Uuid::randomBytes();

        $context = Context::createDefaultContext();

        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext($context),
            [new DeleteCommand(
                $this->definitionInstanceRegistry->get(PaymentMethodDefinition::class),
                ['id' => $paymentMethodId],
                new EntityExistence(PaymentMethodDefinition::ENTITY_NAME, ['id' => $paymentMethodId], true, false, false, [])
            )],
        );

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT id FROM payment_method WHERE id IN (:ids) AND plugin_id IS NOT NULL',
                ['ids' => [$paymentMethodId]],
                ['ids' => ArrayParameterType::BINARY]
            )
            ->willReturn('pluginId');

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Plugin payment methods can not be deleted via API.');
        $subscriber = new PaymentMethodValidator($connection);
        $subscriber->validate($event);
    }

    public function testValidateWithoutCommand(): void
    {
        $context = Context::createDefaultContext();

        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext($context),
            []
        );

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())
            ->method('fetchOne');

        $subscriber = new PaymentMethodValidator($connection);
        $subscriber->validate($event);
    }
}
