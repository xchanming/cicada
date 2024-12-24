<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\SalesChannel\AccountNewsletterRecipientResult;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class AccountNewsletterRecipientRouteTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    private EntityRepository $newsletterRecipientRepository;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);
        $this->newsletterRecipientRepository = static::getContainer()->get('newsletter_recipient.repository');
    }

    public function testNotLoggedin(): void
    {
        $this->browser
            ->request(
                'GET',
                '/store-api/account/newsletter-recipient',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        if (Feature::isActive('v6.7.0.0')) {
            static::assertSame(RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, $response['errors'][0]['code']);
        } else {
            static::assertSame('CHECKOUT__CUSTOMER_NOT_LOGGED_IN', $response['errors'][0]['code']);
        }
    }

    public function testValidNotSubscribed(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $this->createCustomer($email);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => 'cicada',
                ]
            );

        $response = $this->browser->getResponse();

        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);

        $this->browser
            ->request(
                'GET',
                '/store-api/account/newsletter-recipient',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('account_newsletter_recipient', $response['apiAlias']);
        static::assertSame(AccountNewsletterRecipientResult::UNDEFINED, $response['status']);
    }

    public function testValidSubscribed(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $this->createCustomer($email);

        $this->newsletterRecipientRepository->create(
            [
                [
                    'id' => Uuid::randomHex(),
                    'email' => $email,
                    'salesChannelId' => $this->ids->get('sales-channel'),
                    'status' => 'not-set',
                    'hash' => Uuid::randomHex(),
                ],
            ],
            Context::createDefaultContext()
        );

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => 'cicada',
                ]
            );

        $response = $this->browser->getResponse();

        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);

        $this->browser
            ->request(
                'GET',
                '/store-api/account/newsletter-recipient'
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('not-set', $response['status']);
    }

    public function testGuestNotAllowed(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/register',
                $this->getGuestRegistrationData()
            );

        $registerResponse = $this->browser->getResponse();
        static::assertTrue($registerResponse->headers->has(PlatformRequest::HEADER_CONTEXT_TOKEN));
        $contextToken = $registerResponse->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);

        $this->browser
            ->request(
                'GET',
                '/store-api/account/newsletter-recipient',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);

        if (Feature::isActive('v6.7.0.0')) {
            static::assertSame(RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, $response['errors'][0]['code']);
        } else {
            static::assertSame('CHECKOUT__CUSTOMER_NOT_LOGGED_IN', $response['errors'][0]['code']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getGuestRegistrationData(string $storefrontUrl = 'http://localhost'): array
    {
        return [
            'guest' => true,
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'email' => 'teg-reg@example.com',
            'storefrontUrl' => $storefrontUrl,
            'billingAddress' => [
                'countryId' => $this->getValidCountryId(),
                'street' => 'Examplestreet 11',
                'zipcode' => '48441',
                'city' => 'Cologne',
            ],
            'shippingAddress' => [
                'countryId' => $this->getValidCountryId(),
                'salutationId' => $this->getValidSalutationId(),
                'firstName' => 'Test 2',
                'lastName' => 'Example 2',
                'street' => 'Examplestreet 111',
                'zipcode' => '12341',
                'city' => 'Berlin',
            ],
        ];
    }
}
