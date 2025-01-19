<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @internal
 */
#[Package('checkout')]
class AccountProfileControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;

    public function testDeleteCustomerProfile(): void
    {
        $context = Context::createDefaultContext();
        $customer = $this->createCustomer($context);

        $browser = $this->login($customer->getEmail());

        $browser->request('POST', $_SERVER['APP_URL'] . '/account/profile/delete');

        $response = $browser->getResponse();

        static::assertArrayHasKey('success', $this->getFlashBag()->all());
        static::assertTrue($response->isRedirect(), (string) $response->getContent());
    }

    public function testAccountOverviewPageLoadedScriptsAreExecuted(): void
    {
        $context = Context::createDefaultContext();
        $customer = $this->createCustomer($context);

        $browser = $this->login($customer->getEmail());

        $browser->request('GET', '/account');
        $response = $browser->getResponse();

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey('account-overview-page-loaded', $traces);
    }

    public function testAccountProfilePageLoadedScriptsAreExecuted(): void
    {
        $context = Context::createDefaultContext();
        $customer = $this->createCustomer($context);

        $browser = $this->login($customer->getEmail());

        $browser->request('GET', '/account/profile');
        $response = $browser->getResponse();

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey('account-profile-page-loaded', $traces);
    }

    private function login(string $email): KernelBrowser
    {
        $browser = KernelLifecycleManager::createBrowser($this->getKernel());
        $browser->request(
            'POST',
            $_SERVER['APP_URL'] . '/account/login',
            $this->tokenize('frontend.account.login', [
                'username' => $email,
                'password' => '12345678',
            ])
        );
        $response = $browser->getResponse();
        static::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        return $browser;
    }

    private function createCustomer(Context $context): CustomerEntity
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'name' => 'Max',
                'street' => 'Musterstraße 1',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'country' => ['name' => 'Germany'],
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => 'test@example.com',
            'password' => TestDefaults::HASHED_PASSWORD,
            'name' => 'Max',
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        /** @var EntityRepository<CustomerCollection> $repo */
        $repo = static::getContainer()->get('customer.repository');

        $repo->create([$customer], $context);

        $customer = $repo->search(new Criteria([$customerId]), $context)->getEntities()->first();

        static::assertNotNull($customer);

        return $customer;
    }

    private function getFlashBag(): FlashBagInterface
    {
        $session = $this->getSession();

        static::assertInstanceOf(Session::class, $session);

        return $session->getFlashBag();
    }
}
