<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\SalesChannel\ChangeCustomerProfileRoute;
use Cicada\Core\Checkout\Customer\Validation\CustomerValidationFactory;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiCustomFieldMapper;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ChangeCustomerProfileRoute::class)]
class ChangeCustomerProfileRouteTest extends TestCase
{
    public function testCustomFieldsGetPassed(): void
    {
        $customFields = new RequestDataBag(['test1' => '1', 'test2' => '2']);

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository
            ->method('update')
            ->with([
                ['id' => 'customer1', 'company' => '', 'customFields' => ['test1' => '1'], 'salutationId' => '1'],
            ]);

        $storeApiCustomFieldMapper = $this->createMock(StoreApiCustomFieldMapper::class);
        $storeApiCustomFieldMapper
            ->expects(static::once())
            ->method('map')
            ->with('customer', $customFields)
            ->willReturn(['test1' => '1']);

        $change = new ChangeCustomerProfileRoute(
            $customerRepository,
            new EventDispatcher(),
            $this->createMock(DataValidator::class),
            $this->createMock(CustomerValidationFactory::class),
            $storeApiCustomFieldMapper,
            $this->createMock(EntityRepository::class),
        );

        $customer = new CustomerEntity();
        $customer->setId('customer1');
        $data = new RequestDataBag([
            'customFields' => $customFields,
            'salutationId' => '1',
        ]);

        $change->change($data, $this->createMock(SalesChannelContext::class), $customer);
    }

    public function testAccountTypeGetPassed(): void
    {
        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository
            ->method('update')
            ->with(static::callback(function (array $data) {
                static::assertCount(1, $data);
                static::assertIsArray($data[0]);
                static::assertArrayHasKey('accountType', $data[0]);

                return true;
            }));

        $change = new ChangeCustomerProfileRoute(
            $customerRepository,
            new EventDispatcher(),
            $this->createMock(DataValidator::class),
            $this->createMock(CustomerValidationFactory::class),
            $this->createMock(StoreApiCustomFieldMapper::class),
            $this->createMock(EntityRepository::class),
        );

        $customer = new CustomerEntity();
        $customer->setId('customer1');
        $data = new RequestDataBag([
            'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
            'salutationId' => '1',
        ]);

        $change->change($data, $this->createMock(SalesChannelContext::class), $customer);
    }

    public function testSalutationIdIsAssignedDefaultValue(): void
    {
        $salutationId = Uuid::randomHex();

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository
            ->method('update')
            ->with(static::callback(function (array $data) use ($salutationId) {
                static::assertCount(1, $data);
                static::assertIsArray($data[0]);
                static::assertSame($data[0]['salutationId'], $salutationId);

                return true;
            }));

        $idSearchResult = new IdSearchResult(
            1,
            [['data' => $salutationId, 'primaryKey' => $salutationId]],
            new Criteria(),
            Context::createDefaultContext(),
        );

        $salutationRepository = $this->createMock(EntityRepository::class);
        $salutationRepository->method('searchIds')->willReturn($idSearchResult);

        $change = new ChangeCustomerProfileRoute(
            $customerRepository,
            new EventDispatcher(),
            $this->createMock(DataValidator::class),
            $this->createMock(CustomerValidationFactory::class),
            $this->createMock(StoreApiCustomFieldMapper::class),
            $salutationRepository
        );

        $customer = new CustomerEntity();
        $customer->setId('customer1');

        $data = new RequestDataBag([
            'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
            'salutationId' => '',
        ]);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(TestDefaults::SALES_CHANNEL);

        $change->change($data, $salesChannelContext, $customer);
    }
}
