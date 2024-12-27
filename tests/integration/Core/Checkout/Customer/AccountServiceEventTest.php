<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Cicada\Core\Checkout\Customer\Exception\BadCredentialsException;
use Cicada\Core\Checkout\Customer\SalesChannel\AbstractLoginRoute;
use Cicada\Core\Checkout\Customer\SalesChannel\AccountService;
use Cicada\Core\Checkout\Customer\SalesChannel\LoginRoute;
use Cicada\Core\Checkout\Customer\SalesChannel\LogoutRoute;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class AccountServiceEventTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    private AccountService $accountService;

    /**
     * @var EntityRepository<CustomerCollection>
     */
    private EntityRepository $customerRepository;

    private SalesChannelContext $salesChannelContext;

    private AbstractLoginRoute $loginRoute;

    private LogoutRoute $logoutRoute;

    protected function setUp(): void
    {
        $this->accountService = static::getContainer()->get(AccountService::class);
        $this->customerRepository = static::getContainer()->get('customer.repository');
        $this->logoutRoute = static::getContainer()->get(LogoutRoute::class);
        $this->loginRoute = static::getContainer()->get(LoginRoute::class);

        $salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $this->createCustomer('info@example.com');
    }

    public function testLoginBeforeEventNotDispatchedIfNoCredentialsGiven(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $eventDidRun = false;

        $listenerClosure = $this->getEmailListenerClosure($eventDidRun);
        $this->addEventListener($dispatcher, CustomerBeforeLoginEvent::class, $listenerClosure);

        $dataBag = new DataBag();
        $dataBag->add([
            'username' => '',
            'password' => 'cicada',
        ]);

        try {
            $this->loginRoute->login($dataBag->toRequestDataBag(), $this->salesChannelContext);
            $this->accountService->loginByCredentials('', 'cicada', $this->salesChannelContext);
        } catch (BadCredentialsException) {
            // nth
        }
        static::assertFalse($eventDidRun, 'Event "' . CustomerBeforeLoginEvent::class . '" did run');

        $dispatcher->removeListener(CustomerBeforeLoginEvent::class, $listenerClosure);
    }

    public function testLoginEventsDispatched(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $eventsToTest = [
            CustomerBeforeLoginEvent::class,
            CustomerLoginEvent::class,
        ];

        foreach ($eventsToTest as $eventClass) {
            $eventDidRun = false;

            switch ($eventClass) {
                case CustomerBeforeLoginEvent::class:
                    $listenerClosure = $this->getEmailListenerClosure($eventDidRun);

                    break;
                case CustomerLoginEvent::class:
                default:
                    $listenerClosure = $this->getCustomerListenerClosure($eventDidRun);
            }

            $this->addEventListener($dispatcher, $eventClass, $listenerClosure);

            $dataBag = new DataBag();
            $dataBag->add([
                'username' => 'info@example.com',
                'password' => '12345678',
            ]);

            $this->loginRoute->login($dataBag->toRequestDataBag(), $this->salesChannelContext);
            static::assertTrue($eventDidRun, 'Event "' . $eventClass . '" did not run');

            $eventDidRun = false;

            $this->accountService->loginByCredentials('info@example.com', '12345678', $this->salesChannelContext);
            /** @phpstan-ignore-next-line - $eventDidRun updated value on listener */
            static::assertTrue($eventDidRun, 'Event "' . $eventClass . '" did not run');

            $dispatcher->removeListener($eventClass, $listenerClosure);
        }
    }

    public function testLogoutEventsDispatched(): void
    {
        $email = 'info@example.com';
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $eventDidRun = false;

        $listenerClosure = $this->getCustomerListenerClosure($eventDidRun);
        $this->addEventListener($dispatcher, CustomerLogoutEvent::class, $listenerClosure);

        $customer = $this->customerRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('email', $email)),
            $this->salesChannelContext->getContext()
        )->first();

        $this->salesChannelContext->assign(['customer' => $customer]);

        static::assertNotNull($customer = $this->salesChannelContext->getCustomer());
        static::assertSame($email, $customer->getEmail());

        $this->logoutRoute->logout($this->salesChannelContext, new RequestDataBag());

        static::assertTrue($eventDidRun, 'Event "' . CustomerLogoutEvent::class . '" did not run');

        $dispatcher->removeListener(CustomerLogoutEvent::class, $listenerClosure);
    }

    /**
     * @return callable(CustomerBeforeLoginEvent): void
     */
    private function getEmailListenerClosure(bool &$eventDidRun): callable
    {
        return static function (CustomerBeforeLoginEvent $event) use (&$eventDidRun): void {
            $eventDidRun = true;
            static::assertSame('info@example.com', $event->getEmail());
        };
    }

    /**
     * @return callable(CustomerLoginEvent|CustomerLogoutEvent): void
     */
    private function getCustomerListenerClosure(bool &$eventDidRun): callable
    {
        return static function (CustomerLoginEvent|CustomerLogoutEvent $event) use (&$eventDidRun): void {
            $eventDidRun = true;
            static::assertSame('info@example.com', $event->getCustomer()->getEmail());
        };
    }
}
