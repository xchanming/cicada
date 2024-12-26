<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Subscriber;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Event\CustomerDeletedEvent;
use Cicada\Core\Checkout\Customer\Subscriber\CustomerBeforeDeleteSubscriber;
use Cicada\Core\Framework\Api\Serializer\JsonEntityEncoder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityDeleteEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Serializer\StructNormalizer;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Serializer\Serializer;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(CustomerBeforeDeleteSubscriber::class)]
class CustomerBeforeDeleteSubscriberTest extends TestCase
{
    public function testEventsDispatched(): void
    {
        $customerId = Uuid::randomBytes();
        $customer = (new CustomerEntity())
            ->assign([
                'id' => Uuid::fromBytesToHex($customerId),
                'salesChannelId' => Uuid::randomHex(),
                'languageId' => Uuid::randomHex(),
                'customerNumber' => 'SW1000',
                'email' => 'foo@bar.com',
                'name' => 'foo',
            ]);

        $definitionInstanceRegistry = static::createMock(DefinitionInstanceRegistry::class);

        $customerDefinition = new CustomerDefinition();
        $customerDefinition->compile($definitionInstanceRegistry);

        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([
            new EntitySearchResult(
                CustomerEntity::class,
                1,
                new CustomerCollection([$customer]),
                null,
                new Criteria([$customerId]),
                Context::createDefaultContext()
            ),
        ], $customerDefinition);

        $salesChannelContextService = static::createMock(SalesChannelContextService::class);
        $salesChannelContextService->method('get')->willReturn(Generator::createSalesChannelContext());

        $eventDispatcher = new EventDispatcher();

        $structNormalizer = new StructNormalizer();

        $jsonEntityEncoder = new JsonEntityEncoder(new Serializer([$structNormalizer], []));

        $subscriber = new CustomerBeforeDeleteSubscriber(
            $customerRepository,
            $salesChannelContextService,
            $eventDispatcher,
            $jsonEntityEncoder
        );
        $eventDispatcher->addSubscriber($subscriber);

        $entityDeleteEvent = EntityDeleteEvent::create(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [
                new DeleteCommand(
                    $customerDefinition,
                    ['id' => $customerId],
                    new EntityExistence(
                        'customer',
                        ['id' => $customerId],
                        true,
                        false,
                        false,
                        [
                            'exists' => true,
                            'id' => $customerId,
                        ]
                    )
                ),
            ]
        );

        $customerDeletedEventCount = 0;

        $serializedCustomer = $jsonEntityEncoder->encode(
            new Criteria(),
            $customerDefinition,
            $customer,
            '/api/customer'
        );

        $eventDispatcher->addListener(
            CustomerDeletedEvent::class,
            function (CustomerDeletedEvent $event) use (&$customerDeletedEventCount, $customer, $serializedCustomer): void {
                ++$customerDeletedEventCount;
                static::assertSame($customer, $event->getCustomer());

                if (Feature::isActive('v6.7.0.0')) {
                    static::assertSame([
                        'customer' => $serializedCustomer,
                    ], $event->getValues());

                    return;
                }

                static::assertSame([
                    'customer' => $serializedCustomer,
                    'customerId' => $customer->getId(),
                    'customerNumber' => $customer->getCustomerNumber(),
                    'customerEmail' => $customer->getEmail(),
                    'customerName' => $customer->getName(),
                    'customerCompany' => $customer->getCompany(),
                    'customerSalutationId' => $customer->getSalutationId(),
                ], $event->getValues());
            }
        );

        $eventDispatcher->dispatch($entityDeleteEvent);
        $entityDeleteEvent->success();

        static::assertSame(1, $customerDeletedEventCount);
    }
}
