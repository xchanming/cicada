<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Administration\Controller;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AdministrationControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    private Connection $connection;

    private EntityRepository $customerRepository;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $newLanguageId = $this->insertOtherLanguage();
        $this->createSearchConfigFieldForNewLanguage($newLanguageId);

        $this->customerRepository = static::getContainer()->get('customer.repository');
    }

    public function testSnippetRoute(): void
    {
        $this->getBrowser()->request('GET', '/api/_admin/snippets?locale=zh-CN');
        static::assertSame(200, $this->getBrowser()->getResponse()->getStatusCode());
        $content = $this->getBrowser()->getResponse()->getContent();
        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('zh-CN', $response);
        static::assertArrayHasKey('en-GB', $response);
    }

    public function testResetExcludedSearchTermIncorrectLanguageId(): void
    {
        $this->getBrowser()->setServerParameter('HTTP_sw-language-id', Uuid::randomHex());
        $this->getBrowser()->request('POST', '/api/_admin/reset-excluded-search-term');

        $response = $this->getBrowser()->getResponse();

        static::assertSame(412, $response->getStatusCode());
    }

    public function testValidateEmailSuccess(): void
    {
        $browser = $this->createClient();
        $this->createCustomer(['email' => 'foo@bar.de']);

        $browser->request(
            'POST',
            '/api/_admin/check-customer-email-valid',
            [
                'id' => Uuid::randomHex(),
                'email' => 'foo1@bar.de',
                'boundSalesChannelId' => null,
            ]
        );

        $content = $this->getBrowser()->getResponse()->getContent();
        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(200, $browser->getResponse()->getStatusCode());
        static::assertArrayHasKey('isValid', $response);
    }

    public function testValidateEmailFail(): void
    {
        $email = 'foo@bar.de';
        $browser = $this->createClient();
        $this->createCustomer(['email' => 'foo@bar.de']);

        $browser->request(
            'POST',
            '/api/_admin/check-customer-email-valid',
            [
                'id' => Uuid::randomHex(),
                'email' => $email,
                'boundSalesChannelId' => null,
            ]
        );

        $content = $this->getBrowser()->getResponse()->getContent();
        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(400, $browser->getResponse()->getStatusCode());
        static::assertSame('The email address ' . $email . ' is already in use', $response['errors'][0]['detail']);
    }

    public function testValidateEmailSuccessWithSameCustomerDifferentSalesChannel(): void
    {
        $this->setCustomerBoundToSalesChannels(true);
        $newSalesChannel = $this->createSalesChannel();

        $browser = $this->createClient();
        $email = 'foo@bar.de';
        $this->createCustomer(['email' => 'foo@bar.de', 'boundSalesChannelId' => TestDefaults::SALES_CHANNEL]);

        $browser->request(
            'POST',
            '/api/_admin/check-customer-email-valid',
            [
                'id' => Uuid::randomHex(),
                'email' => $email,
                'boundSalesChannelId' => $newSalesChannel['id'],
            ]
        );

        $content = $this->getBrowser()->getResponse()->getContent();
        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(200, $browser->getResponse()->getStatusCode());
        static::assertArrayHasKey('isValid', $response);
    }

    public function testValidateEmailFailWithSameCustomerSameSalesChannel(): void
    {
        $this->setCustomerBoundToSalesChannels(true);
        $email = 'foo@bar.de';
        $browser = $this->createClient();
        $this->createCustomer(['email' => 'foo@bar.de', 'boundSalesChannelId' => TestDefaults::SALES_CHANNEL]);

        $browser->request(
            'POST',
            '/api/_admin/check-customer-email-valid',
            [
                'id' => Uuid::randomHex(),
                'email' => $email,
                'boundSalesChannelId' => TestDefaults::SALES_CHANNEL,
            ]
        );

        $content = $this->getBrowser()->getResponse()->getContent();
        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(400, $browser->getResponse()->getStatusCode());
        static::assertSame('The email address ' . $email . ' is already in use in the Sales Channel Headless', $response['errors'][0]['detail']);
    }

    public function testValidateEmailFailWithSameCustomerIsAlreadyExistsInAllSalesChannel(): void
    {
        $this->setCustomerBoundToSalesChannels(true);
        $email = 'foo@bar.de';
        $browser = $this->createClient();
        $this->createCustomer(['email' => $email]);

        $browser->request(
            'POST',
            '/api/_admin/check-customer-email-valid',
            [
                'id' => Uuid::randomHex(),
                'email' => $email,
                'boundSalesChannelId' => TestDefaults::SALES_CHANNEL,
            ]
        );

        $content = $this->getBrowser()->getResponse()->getContent();
        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(400, $browser->getResponse()->getStatusCode());
        static::assertSame('The email address ' . $email . ' is already in use', $response['errors'][0]['detail']);
    }

    public function testPreviewSanitizedHtml(): void
    {
        $html = '<img alt="" src="#" /><script type="text/javascript"></script><div>test</div>';
        $browser = $this->createClient();

        $browser->request(
            'POST',
            '/api/_admin/sanitize-html',
            [
                'html' => $html,
                'field' => 'product_translation.description',
            ]
        );

        $content = $this->getBrowser()->getResponse()->getContent();

        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $browser->getResponse()->getStatusCode());
        static::assertSame('<img alt="" src="#" /><div>test</div>', $response['preview']);

        $browser->request(
            'POST',
            '/api/_admin/sanitize-html',
            [
                'html' => $html,
                'field' => 'mail_template_translation.contentHtml',
            ]
        );

        $content = $this->getBrowser()->getResponse()->getContent();

        static::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $browser->getResponse()->getStatusCode());
        static::assertSame($html, $response['preview']);
    }

    /**
     * @param array<string, string|bool|int|float|null> $overrideData
     */
    private function createCustomer(array $overrideData): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = array_merge([
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'name' => 'Max',
                'street' => 'Musterstraße 1',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => 'random@mail.com',
            'password' => TestDefaults::HASHED_PASSWORD,
            'title' => 'Max',
            'guest' => false,
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ], $overrideData);
        $this->customerRepository->create([$customer], Context::createDefaultContext());

        return $customerId;
    }

    private function insertOtherLanguage(): string
    {
        $langId = $this->connection->executeQuery(
            'SELECT id FROM `language` WHERE `name` = :langName',
            [
                'langName' => 'English',
            ]
        )->fetchFirstColumn();

        $localeId = $this->connection->executeQuery(
            'SELECT id FROM `locale` WHERE `code` = :code',
            [
                'code' => 'en-US',
            ]
        )->fetchFirstColumn();

        if ($langId) {
            return $langId[0];
        }

        $newLanguageId = Uuid::randomBytes();
        $statement = $this->connection->prepare('INSERT INTO `language` (`id`, `name`, `locale_id`, `translation_code_id`, `created_at`)
            VALUES (?, ?, ?, ?, ?)');
        $statement->executeStatement([$newLanguageId, 'Vietnamese', $localeId[0], $localeId[0], '2021-04-01 04:41:12.045']);

        return $newLanguageId;
    }

    private function createSearchConfigFieldForNewLanguage(string $newLanguageId): void
    {
        $configId = $this->connection->executeQuery(
            'SELECT id FROM `product_search_config` WHERE `language_id` = :languageId',
            [
                'languageId' => $newLanguageId,
            ]
        )->fetchFirstColumn();

        if (!$configId) {
            $newConfigId = Uuid::randomBytes();
            $statement = $this->connection->prepare('INSERT INTO `product_search_config` (`id`, `language_id`, `and_logic`, `min_search_length`, `created_at`)
                VALUES (?, ?, ?, ?, ?)');
            $statement->executeStatement([$newConfigId, $newLanguageId, 0, 2, '2021-04-01 04:41:12.045']);
        }
    }

    private function setCustomerBoundToSalesChannels(bool $value): void
    {
        static::getContainer()
            ->get(SystemConfigService::class)
            ->set('core.systemWideLoginRegistration.isCustomerBoundToSalesChannel', $value);
    }

    /**
     * @param array<string, int|float|string|bool|null> $salesChannelOverride
     *
     * @return array<string, array<int, array<string, string|null>>|bool|float|int|string|null>
     */
    private function createSalesChannel(array $salesChannelOverride = []): array
    {
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = static::getContainer()->get('sales_channel.repository');
        $paymentMethod = $this->getAvailablePaymentMethod();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('domains.url', 'http://localhost'));
        $salesChannelIds = $salesChannelRepository->searchIds($criteria, Context::createDefaultContext());

        if (!isset($salesChannelOverride['domains']) && $salesChannelIds->firstId() !== null) {
            $salesChannelRepository->delete([['id' => $salesChannelIds->firstId()]], Context::createDefaultContext());
        }

        $salesChannel = array_merge([
            'id' => $salesChannelOverride['id'] ?? Uuid::randomHex(),
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => 'new sales channel',
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
            'currencyId' => Defaults::CURRENCY,
            'paymentMethodId' => $paymentMethod->getId(),
            'paymentMethods' => [['id' => $paymentMethod->getId()]],
            'shippingMethodId' => $this->getAvailableShippingMethod()->getId(),
            'navigationCategoryId' => $this->getValidCategoryId(),
            'countryId' => $this->getValidCountryId(null),
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => $salesChannelOverride['languages'] ?? [['id' => Defaults::LANGUAGE_SYSTEM]],
            'customerGroupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'domains' => [
                [
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => 'http://localhost',
                ],
            ],
            'countries' => [['id' => $this->getValidCountryId(null)]],
        ], $salesChannelOverride);

        $salesChannelRepository->upsert([$salesChannel], Context::createDefaultContext());

        return $salesChannel;
    }
}
