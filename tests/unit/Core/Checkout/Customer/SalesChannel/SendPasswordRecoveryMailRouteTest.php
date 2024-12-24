<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerRecovery\CustomerRecoveryCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerRecovery\CustomerRecoveryEntity;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Checkout\Customer\Event\CustomerAccountRecoverRequestEvent;
use Cicada\Core\Checkout\Customer\SalesChannel\SendPasswordRecoveryMailRoute;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\RateLimiter\RateLimiter;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(SendPasswordRecoveryMailRoute::class)]
class SendPasswordRecoveryMailRouteTest extends TestCase
{
    protected EntityRepository&MockObject $customerRepository;

    protected EntityRepository&MockObject $customerRecoveryRepository;

    protected EventDispatcherInterface&MockObject $eventDispatcher;

    protected DataValidator&MockObject $validator;

    protected SystemConfigService&MockObject $systemConfigService;

    protected RequestStack&MockObject $requestStack;

    protected RateLimiter&MockObject $rateLimiter;

    protected SalesChannelContext $context;

    protected function setUp(): void
    {
        $this->customerRepository = $this->createMock(EntityRepository::class);
        $this->customerRecoveryRepository = $this->createMock(EntityRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->validator = $this->createMock(DataValidator::class);
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->rateLimiter = $this->createMock(RateLimiter::class);
        $this->context = Generator::createSalesChannelContext();
    }

    public function testSendRecoveryMail(): void
    {
        $customer = new CustomerEntity();
        $customer->setId('foo');

        $customerCollection = new CustomerCollection([$customer]);

        $this->customerRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(
                new EntitySearchResult(
                    'customer',
                    1,
                    $customerCollection,
                    null,
                    new Criteria(),
                    Context::createDefaultContext()
                )
            );

        $this->customerRecoveryRepository
            ->expects(static::once())
            ->method('create')
            ->with(
                static::callback(function (array $recoveryData): bool {
                    static::assertCount(1, $recoveryData);

                    $updateData = $recoveryData[0];

                    static::assertArrayHasKey('customerId', $updateData);
                    static::assertArrayHasKey('hash', $updateData);

                    static::assertSame('foo', $updateData['customerId']);
                    static::assertSame(32, \strlen($updateData['hash']));

                    return true;
                }),
                $this->context->getContext()
            );

        $customerRecovery = new CustomerRecoveryEntity();
        $customerRecovery->setId('customer-recovery-id');
        $customerRecovery->setUniqueIdentifier('customer-recovery-id');
        $customerRecovery->setCustomerId($customer->getId());
        $customerRecovery->setHash('super-secret-hash');
        $customerRecovery->setCustomer($customer);

        $customerRecoveryCollection = new CustomerRecoveryCollection([$customerRecovery]);

        $this->customerRecoveryRepository
            ->expects(static::exactly(2))
            ->method('search')
            ->willReturn(
                new EntitySearchResult(
                    'customer_recovery',
                    1,
                    $customerRecoveryCollection,
                    null,
                    new Criteria(),
                    Context::createDefaultContext()
                )
            );

        $MailRoute = new SendPasswordRecoveryMailRoute(
            $this->customerRepository,
            $this->customerRecoveryRepository,
            $this->eventDispatcher,
            $this->validator,
            $this->systemConfigService,
            $this->requestStack,
            $this->rateLimiter
        );

        $this->context->getSalesChannel()->setTranslated(['name' => 'FooBar']);
        $event = new CustomerAccountRecoverRequestEvent($this->context, $customerRecovery, 'https://test.example.dev/account/recover/password?hash=super-secret-hash');

        $this->eventDispatcher
            ->method('dispatch')
            ->with(static::callback(function (Event $dispatched) use ($event): bool {
                if ($dispatched instanceof CustomerAccountRecoverRequestEvent) {
                    static::assertEquals($event, $dispatched);
                }

                return true;
            }), static::anything());

        $data = new RequestDataBag();
        $data->set('email', 'test@test.dev');
        $data->set('storefrontUrl', 'https://test.example.dev');

        $MailRoute->sendRecoveryMail($data, $this->context);
    }

    public function testNoCustomerFound(): void
    {
        $MailRoute = new SendPasswordRecoveryMailRoute(
            $this->customerRepository,
            $this->customerRecoveryRepository,
            $this->eventDispatcher,
            $this->validator,
            $this->systemConfigService,
            $this->requestStack,
            $this->rateLimiter
        );

        $data = new RequestDataBag();
        $data->set('email', 'foo@foo');

        static::expectException(CustomerException::class);
        static::expectExceptionMessage('No matching customer for the email "foo@foo" was found.');

        $MailRoute->sendRecoveryMail($data, $this->context);
    }
}
