<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class ChangeLanguageRouteTest extends TestCase
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
    }

    public function testNotLoggedIn(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-language',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertSame(RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, $response['errors'][0]['code']);
    }

    public function testValidLanguage(): void
    {
        $languageId = $this->getZhCnLanguageId();

        static::getContainer()->get('sales_channel.repository')->update(
            [
                [
                    'id' => $this->ids->get('sales-channel'),
                    'languages' => [
                        [
                            'id' => $this->getZhCnLanguageId(),
                        ],
                    ],
                ],
            ],
            Context::createDefaultContext()
        );

        $id = $this->login($this->browser);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-language',
                [
                    'languageId' => $languageId,
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('success', $response);

        /** @var Connection $connection */
        $connection = static::getContainer()->get(Connection::class);
        $customer = $connection->fetchAllAssociative('SELECT * FROM customer WHERE id = :id', ['id' => Uuid::fromHexToBytes($id)]);

        static::assertEquals($languageId, Uuid::fromBytesToHex($customer[0]['language_id']));
    }

    public function testInvalidLanguage(): void
    {
        $languageId = $this->getZhCnLanguageId();

        $id = $this->login($this->browser);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-language',
                [
                    'languageId' => $languageId,
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertEquals('The "language" entity with id "' . $languageId . '" does not exist.', $response['errors'][0]['detail']);

        /** @var Connection $connection */
        $connection = static::getContainer()->get(Connection::class);
        $customer = $connection->fetchAllAssociative('SELECT * FROM customer WHERE id = :id', ['id' => Uuid::fromHexToBytes($id)]);

        static::assertEquals(Defaults::LANGUAGE_SYSTEM, Uuid::fromBytesToHex($customer[0]['language_id']));
    }
}
