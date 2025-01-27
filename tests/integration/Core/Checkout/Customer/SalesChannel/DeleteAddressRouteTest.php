<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Framework\Log\Package;
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
class DeleteAddressRouteTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);

        $email = Uuid::randomHex() . '@example.com';
        $this->createCustomer($email);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => '12345678',
                ]
            );

        $response = $this->browser->getResponse();

        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);
    }

    public function testDeleteNewCreatedAddress(): void
    {
        // Create
        $data = [
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Test',
            'street' => 'Test',
            'phoneNumber' => 'Test',
            'cityId' => $this->getValidCountryCityId(),
            'countryId' => $this->getValidCountryId(),
            'districtId' => $this->getValidCountryDistrictId(),
        ];

        $this->browser
            ->request(
                'POST',
                '/store-api/account/address',
                $data
            );

        $addressId = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['id'];

        // Check is listed
        $this->browser
            ->request(
                'POST',
                '/store-api/account/list-address',
                [
                ]
            );

        static::assertSame(2, json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['total']);

        // Delete
        $this->browser
            ->request(
                'DELETE',
                '/store-api/account/address/' . $addressId
            );

        static::assertSame(204, $this->browser->getResponse()->getStatusCode());

        $this->browser
            ->request(
                'POST',
                '/store-api/account/list-address',
                [
                ]
            );

        static::assertSame(1, json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['total']);
    }

    public function testDeleteDefaultAddress(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/customer',
                []
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $billingAddressId = $response['defaultBillingAddressId'];
        $shippingAddressId = $response['defaultShippingAddressId'];

        $this->browser
            ->request(
                'DELETE',
                '/store-api/account/address/' . $billingAddressId
            );

        static::assertNotSame(204, $this->browser->getResponse()->getStatusCode());
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('CHECKOUT__CUSTOMER_ADDRESS_IS_DEFAULT', $response['errors'][0]['code']);

        $this->browser
            ->request(
                'DELETE',
                '/store-api/account/address/' . $shippingAddressId
            );

        static::assertNotSame(204, $this->browser->getResponse()->getStatusCode());
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('CHECKOUT__CUSTOMER_ADDRESS_IS_DEFAULT', $response['errors'][0]['code']);
    }

    #[Group('mysample')]
    public function testDeleteActiveAddress(): void
    {
        $data = [
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Test',
            'street' => 'Test',
            'phoneNumber' => 'Test',
            'cityId' => $this->getValidCountryCityId(),
            'countryId' => $this->getValidCountryId(),
            'districtId' => $this->getValidCountryDistrictId(),
        ];

        $this->browser
            ->request(
                'POST',
                '/store-api/account/address',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                (string) \json_encode($data, \JSON_THROW_ON_ERROR)
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $addressId = $response['id'];

        $contextData = [
            'billingAddressId' => $addressId,
            'shippingAddressId' => $addressId,
        ];

        $this->browser
        ->request(
            'PATCH',
            '/store-api/context',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) \json_encode($contextData, \JSON_THROW_ON_ERROR)
        );

        $this->browser
            ->request(
                'DELETE',
                '/store-api/account/address/' . $addressId
            );

        static::assertNotSame(204, $this->browser->getResponse()->getStatusCode());
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('CHECKOUT__CUSTOMER_ADDRESS_IS_ACTIVE', $response['errors'][0]['code']);
    }

    public function testDeleteNewCreatedAddressGuest(): void
    {
        $customerId = $this->createCustomer(null, true);
        $context = $this->getLoggedInContextToken($customerId, $this->ids->get('sales-channel'));
        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $context);

        // Create
        $data = [
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Test',
            'street' => 'Test',
            'zipcode' => 'Test',
            'cityId' => $this->getValidCountryCityId(),
            'countryId' => $this->getValidCountryId(),
            'phoneNumber' => '1234567890',
            'districtId' => $this->getValidCountryDistrictId(),
        ];

        $this->browser
            ->request(
                'POST',
                '/store-api/account/address',
                $data
            );

        $addressId = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['id'];

        // Check is listed
        $this->browser
            ->request(
                'POST',
                '/store-api/account/list-address',
                [
                ]
            );

        static::assertSame(2, json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['total']);

        // Delete
        $this->browser
            ->request(
                'DELETE',
                '/store-api/account/address/' . $addressId
            );

        static::assertSame(204, $this->browser->getResponse()->getStatusCode());

        $this->browser
            ->request(
                'POST',
                '/store-api/account/list-address',
                [
                ]
            );

        static::assertSame(1, json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['total']);
    }
}
