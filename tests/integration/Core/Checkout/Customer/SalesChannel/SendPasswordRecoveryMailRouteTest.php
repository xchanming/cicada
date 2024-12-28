<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\Event\CustomerAccountRecoverRequestEvent;
use Cicada\Core\Checkout\Customer\Event\PasswordRecoveryUrlEvent;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class SendPasswordRecoveryMailRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    private EntityRepository $customerRepository;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);
        $this->customerRepository = static::getContainer()->get('customer.repository');
    }

    public function testResetUnknownEmail(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password',
                [
                    'email' => 'lol@lol.de',
                    'storefrontUrl' => 'http://localhost',
                ]
            );

        $response = json_decode($this->browser->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertSame('CHECKOUT__CUSTOMER_NOT_FOUND', $response['errors'][0]['code']);
    }

    public function testResetWithInvalidUrl(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password',
                [
                    'email' => 'lol@lol.de',
                    'storefrontUrl' => 'http://aaaa.de',
                ]
            );

        $response = json_decode($this->browser->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertSame('VIOLATION::NO_SUCH_CHOICE_ERROR', $response['errors'][0]['code']);
    }

    public function testResetWithTryingToDisableValidation(): void
    {
        $this->createCustomer('foo-test@test.de');

        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password?validateStorefrontUrl=false',
                [
                    'email' => 'foo-test@test.de',
                    'storefrontUrl' => 'http://my-evil-page',
                    'validateStorefrontUrl' => false,
                ]
            );

        static::assertSame(400, $this->browser->getResponse()->getStatusCode());

        $response = json_decode($this->browser->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('VIOLATION::NO_SUCH_CHOICE_ERROR', $response['errors'][0]['code']);
    }

    public function testResetWithDisabledAccount(): void
    {
        $email = 'test-disabled@test.de';

        $this->createCustomer($email, false);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password?validateStorefrontUrl=false',
                [
                    'email' => $email,
                    'storefrontUrl' => 'http://localhost',
                    'validateStorefrontUrl' => false,
                ]
            );

        static::assertSame(401, $this->browser->getResponse()->getStatusCode());

        $response = json_decode($this->browser->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('CHECKOUT__CUSTOMER_NOT_FOUND', $response['errors'][0]['code']);
    }

    /**
     * @param array{domain: string, expectDomain: string} $domainUrlTest
     */
    #[DataProvider('sendMailWithDomainAndLeadingSlashProvider')]
    public function testSendMailWithDomainAndLeadingSlash(array $domainUrlTest): void
    {
        $this->createCustomer('foo-test@test.de');

        $this->addDomain($domainUrlTest['domain']);

        $caughtEvent = null;
        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            CustomerAccountRecoverRequestEvent::EVENT_NAME,
            static function (CustomerAccountRecoverRequestEvent $event) use (&$caughtEvent): void {
                $caughtEvent = $event;
            }
        );

        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password',
                [
                    'email' => 'foo-test@test.de',
                    'storefrontUrl' => $domainUrlTest['expectDomain'],
                ]
            );

        static::assertEquals(200, $this->browser->getResponse()->getStatusCode());

        /** @var CustomerAccountRecoverRequestEvent $caughtEvent */
        static::assertInstanceOf(CustomerAccountRecoverRequestEvent::class, $caughtEvent);
        static::assertStringStartsWith('http://my-evil-page/account/', $caughtEvent->getResetUrl());
    }

    public function testSendMailWithChangedUrl(): void
    {
        $this->createCustomer('foo-test@test.de');

        $systemConfigService = static::getContainer()->get(SystemConfigService::class);
        $systemConfigService->set('core.loginRegistration.pwdRecoverUrl', '/test/rec/password/%%RECOVERHASH%%"');

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $caughtEvent = null;
        $this->addEventListener(
            $dispatcher,
            CustomerAccountRecoverRequestEvent::EVENT_NAME,
            static function (CustomerAccountRecoverRequestEvent $event) use (&$caughtEvent): void {
                $caughtEvent = $event;
            }
        );

        $this->addEventListener(
            $dispatcher,
            PasswordRecoveryUrlEvent::class,
            static function (PasswordRecoveryUrlEvent $event): void {
                $event->setRecoveryUrl($event->getRecoveryUrl() . '/?somethingSpecial=1');
            }
        );

        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password',
                [
                    'email' => 'foo-test@test.de',
                    'storefrontUrl' => 'http://localhost',
                ]
            );

        static::assertEquals(200, $this->browser->getResponse()->getStatusCode(), $this->browser->getResponse()->getContent() ?: '');

        /** @var CustomerAccountRecoverRequestEvent $caughtEvent */
        static::assertInstanceOf(CustomerAccountRecoverRequestEvent::class, $caughtEvent);
        static::assertStringStartsWith('http://localhost/test/rec/password/', $caughtEvent->getResetUrl());
        static::assertStringEndsWith('/?somethingSpecial=1', $caughtEvent->getResetUrl());
    }

    /**
     * @return array<array{0: array{domain: string, expectDomain: string}}>
     */
    public static function sendMailWithDomainAndLeadingSlashProvider(): array
    {
        return [
            // test without leading slash
            [
                ['domain' => 'http://my-evil-page', 'expectDomain' => 'http://my-evil-page'],
            ],
            // test with leading slash
            [
                ['domain' => 'http://my-evil-page/', 'expectDomain' => 'http://my-evil-page'],
            ],
            // test with double leading slash
            [
                ['domain' => 'http://my-evil-page//', 'expectDomain' => 'http://my-evil-page'],
            ],
        ];
    }

    private function addDomain(string $url): void
    {
        $snippetSetId = static::getContainer()->get(Connection::class)
            ->fetchOne('SELECT LOWER(HEX(id)) FROM snippet_set LIMIT 1');

        $domain = [
            'salesChannelId' => $this->ids->create('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'url' => $url,
            'currencyId' => Defaults::CURRENCY,
            'snippetSetId' => $snippetSetId,
        ];

        static::getContainer()->get('sales_channel_domain.repository')
            ->create([$domain], Context::createDefaultContext());
    }

    private function createCustomer(?string $email = null, bool $active = true): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'active' => $active,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'name' => 'Max',
                'street' => 'Musterstraße 1',
                'city' => 'Schoöppingen',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => $email,
            'password' => TestDefaults::HASHED_PASSWORD,
            'name' => 'Max',
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        $this->customerRepository->create([$customer], Context::createDefaultContext());

        return $customerId;
    }
}
