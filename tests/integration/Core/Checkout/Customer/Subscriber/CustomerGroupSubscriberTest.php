<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\Subscriber;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerGroupSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    /**
     * @var EntityRepository<CustomerCollection>
     */
    private EntityRepository $customerGroupRepository;

    /**
     * @var EntityRepository<SeoUrlCollection>
     */
    private EntityRepository $seoRepository;

    protected function setUp(): void
    {
        $this->customerGroupRepository = static::getContainer()->get('customer_group.repository');
        $this->seoRepository = static::getContainer()->get('seo_url.repository');
    }

    public function testUrlsAreNotWritten(): void
    {
        $id = Uuid::randomHex();

        $this->customerGroupRepository->create([
            [
                'id' => $id,
                'name' => 'Test',
            ],
        ], Context::createDefaultContext());

        $urls = $this->getSeoUrlsById($id);

        static::assertCount(0, $urls);
    }

    public function testUrlsAreWrittenToOnlyAssignedSalesChannel(): void
    {
        $s1 = $this->createSalesChannel()['id'];

        $id = Uuid::randomHex();

        $this->customerGroupRepository->create([
            [
                'id' => $id,
                'name' => 'Test',
                'registrationActive' => true,
                'registrationTitle' => 'test',
                'registrationSalesChannels' => [['id' => $s1]],
            ],
        ], Context::createDefaultContext());

        $urls = $this->getSeoUrlsById($id);

        static::assertCount(1, $urls);

        $url = $urls->first();

        static::assertNotNull($url);
        static::assertSame($s1, $url->getSalesChannelId());
        static::assertSame($id, $url->getForeignKey());
        static::assertSame('frontend.account.customer-group-registration.page', $url->getRouteName());
        static::assertSame('test', $url->getSeoPathInfo());
    }

    public function testUrlsAreForHeadlessSalesChannelAreHanldedCorrectly(): void
    {
        $s1 = $this->createSalesChannel(['typeId' => Defaults::SALES_CHANNEL_TYPE_API])['id'];

        $id = Uuid::randomHex();

        $this->customerGroupRepository->create([
            [
                'id' => $id,
                'name' => 'Test',
                'registrationActive' => true,
                'registrationTitle' => 'test',
                'registrationSalesChannels' => [['id' => $s1]],
            ],
        ], Context::createDefaultContext());

        $urls = $this->getSeoUrlsById($id);

        static::assertCount(0, $urls);
    }

    public function testUrlsAreNotWrittenWhenRegistrationIsDisabled(): void
    {
        $s1 = $this->createSalesChannel()['id'];

        $id = Uuid::randomHex();

        $this->customerGroupRepository->create([
            [
                'id' => $id,
                'name' => 'Test',
                'registrationActive' => false,
                'registrationTitle' => 'test',
                'registrationSalesChannels' => [['id' => $s1]],
            ],
        ], Context::createDefaultContext());

        $urls = $this->getSeoUrlsById($id);

        static::assertCount(0, $urls);
    }

    public function testUrlExistsForAllLanguages(): void
    {
        $s1 = $this->createSalesChannel()['id'];

        $languageIds = array_values(static::getContainer()->get('language.repository')->search(new Criteria(), Context::createDefaultContext())->getIds());

        $upsertLanguages = [];
        foreach ($languageIds as $id) {
            if ($id === Defaults::LANGUAGE_SYSTEM) {
                continue;
            }

            $upsertLanguages[] = ['id' => $id];
        }

        static::getContainer()->get('sales_channel.repository')->upsert([
            [
                'id' => $s1,
                'languages' => $upsertLanguages,
            ],
        ], Context::createDefaultContext());

        $id = Uuid::randomHex();

        $this->customerGroupRepository->create([
            [
                'id' => $id,
                'name' => 'Test',
                'registrationActive' => true,
                'registrationTitle' => 'test',
                'registrationSalesChannels' => [['id' => $s1]],
            ],
        ], Context::createDefaultContext());

        $urls = $this->getSeoUrlsById($id);

        static::assertCount(\count($languageIds), $urls);

        foreach ($languageIds as $languageId) {
            $foundUrl = false;

            foreach ($urls->getElements() as $url) {
                if ($url->getLanguageId() === $languageId) {
                    static::assertSame('test', $url->getSeoPathInfo());
                    static::assertSame($s1, $url->getSalesChannelId());
                    $foundUrl = true;
                }
            }

            static::assertTrue($foundUrl, \sprintf('Cannot find url for language "%s"', $languageId));
        }
    }

    public function testCreatedUrlsAreDeletedWhenGroupIsDeleted(): void
    {
        $s1 = $this->createSalesChannel()['id'];

        $id = Uuid::randomHex();

        $this->customerGroupRepository->create([
            [
                'id' => $id,
                'name' => 'Test',
                'registrationActive' => true,
                'registrationTitle' => 'test',
                'registrationSalesChannels' => [['id' => $s1]],
            ],
        ], Context::createDefaultContext());

        static::assertCount(1, $this->getSeoUrlsById($id));

        $this->customerGroupRepository->delete([['id' => $id]], Context::createDefaultContext());

        static::assertCount(0, $this->getSeoUrlsById($id));
    }

    public function testSaveGroupAndEnableLaterSalesChannels(): void
    {
        $s1 = $this->createSalesChannel()['id'];

        $id = Uuid::randomHex();

        $this->customerGroupRepository->create([
            [
                'id' => $id,
                'name' => 'Test',
                'registrationActive' => true,
                'registrationTitle' => 'test',
            ],
        ], Context::createDefaultContext());

        $this->customerGroupRepository->upsert([
            [
                'id' => $id,
                'registrationSalesChannels' => [['id' => $s1]],
            ],
        ], Context::createDefaultContext());

        $urls = $this->getSeoUrlsById($id);

        static::assertCount(1, $urls);

        $url = $urls->first();

        static::assertNotNull($url);
        static::assertSame($s1, $url->getSalesChannelId());
        static::assertSame($id, $url->getForeignKey());
        static::assertSame('frontend.account.customer-group-registration.page', $url->getRouteName());
        static::assertSame('test', $url->getSeoPathInfo());
    }

    private function getSeoUrlsById(string $id): SeoUrlCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('foreignKey', $id));

        /** @var SeoUrlCollection $result */
        $result = $this->seoRepository->search($criteria, Context::createDefaultContext())->getEntities();

        return $result;
    }

    /**
     * @param array<string, mixed> $salesChannelOverride
     *
     * @return array<string, mixed>
     */
    private function createSalesChannel(array $salesChannelOverride = []): array
    {
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = static::getContainer()->get('sales_channel.repository');
        $paymentMethod = $this->getAvailablePaymentMethod();
        $salesChannel = array_merge([
            'id' => Uuid::randomHex(),
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => 'API Test case sales channel',
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
            'currencyId' => Defaults::CURRENCY,
            'paymentMethodId' => $paymentMethod->getId(),
            'paymentMethods' => [['id' => $paymentMethod->getId()]],
            'shippingMethodId' => $this->getAvailableShippingMethod()->getId(),
            'navigationCategoryId' => $this->getValidCategoryId(),
            'countryId' => $this->getValidCountryId(),
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'customerGroupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'domains' => [
                [
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => 'http://localhost/' . Uuid::randomHex(),
                ],
            ],
        ], $salesChannelOverride);

        $salesChannelRepository->upsert([$salesChannel], Context::createDefaultContext());

        return $salesChannel;
    }
}
