<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Sitemap\ScheduledTask;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Cicada\Core\Content\Sitemap\ScheduledTask\SitemapGenerateTaskHandler;
use Cicada\Core\Content\Sitemap\ScheduledTask\SitemapMessage;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\Seo\StorefrontSalesChannelTestHelper;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('services-settings')]
class SitemapGenerateTaskHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelFunctionalTestBehaviour;
    use StorefrontSalesChannelTestHelper;

    private SitemapGenerateTaskHandler $sitemapHandler;

    private EntityRepository $salesChannelDomainRepository;

    private EntityRepository $salesChannelRepository;

    private MockObject&MessageBusInterface $messageBusMock;

    protected function setUp(): void
    {
        $this->salesChannelRepository = static::getContainer()->get('sales_channel.repository');
        $this->messageBusMock = $this->createMock(MessageBusInterface::class);
        $this->sitemapHandler = new SitemapGenerateTaskHandler(
            static::getContainer()->get('scheduled_task.repository'),
            $this->createMock(LoggerInterface::class),
            $this->salesChannelRepository,
            static::getContainer()->get(SystemConfigService::class),
            $this->messageBusMock,
            static::getContainer()->get('event_dispatcher')
        );
        $this->salesChannelDomainRepository = static::getContainer()->get('sales_channel_domain.repository');
    }

    public function testNotHandelDuplicateWithSameLanguage(): void
    {
        /** @var list<string> $salesChannelIds */
        $salesChannelIds = $this->salesChannelRepository->searchIds(new Criteria(), Context::createDefaultContext())->getIds();

        $salesChannelContext = $this->createStorefrontSalesChannelContext(Uuid::randomHex(), 'test-sitemap-task-handler');

        $nonDefaults = array_values(array_filter(array_map(function (string $id): ?array {
            if ($id === TestDefaults::SALES_CHANNEL) {
                return null;
            }

            return ['id' => $id];
        }, $salesChannelIds)));

        $this->salesChannelRepository->delete($nonDefaults, Context::createDefaultContext());

        $this->salesChannelDomainRepository->create([
            [
                'salesChannelId' => $salesChannelContext->getSalesChannelId(),
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'https://test.com',
            ],
            [
                'salesChannelId' => $salesChannelContext->getSalesChannelId(),
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'https://test.de',
            ],
        ], Context::createDefaultContext());

        $message = new SitemapMessage(
            TestDefaults::SALES_CHANNEL,
            Defaults::LANGUAGE_SYSTEM,
            null,
            null,
            true
        );

        $this->messageBusMock->expects(static::once())
            ->method('dispatch')
            ->willReturn(new Envelope($message));

        $this->sitemapHandler->run();
    }

    public function testItGeneratesCorrectMessagesIfLastLanguageIsFirstOfNextSalesChannel(): void
    {
        /** @var list<string> $salesChannelIds */
        $salesChannelIds = $this->salesChannelRepository->searchIds(new Criteria(), Context::createDefaultContext())->getIds();

        $nonDefaults = array_values(array_filter(array_map(function (string $id): ?array {
            if ($id === TestDefaults::SALES_CHANNEL) {
                return null;
            }

            return ['id' => $id];
        }, $salesChannelIds)));

        $this->salesChannelRepository->delete($nonDefaults, Context::createDefaultContext());

        // trick the sorting by making sure the new sales channel id is greater than the default sales channel id
        $newSalesChannelId = substr_replace(TestDefaults::SALES_CHANNEL, 'f', 0, 1);

        $paymentMethod = $this->getAvailablePaymentMethod();

        $this->salesChannelDomainRepository->create([
            [
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'https://test.com',
            ],
            [
                'salesChannelId' => $newSalesChannelId,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'https://test.de',
                'salesChannel' => [
                    'id' => $newSalesChannelId,
                    'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
                    'name' => 'Test',
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
                    'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
                    'customerGroupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
                ],
            ],
        ], Context::createDefaultContext());

        $message = new SitemapMessage(
            TestDefaults::SALES_CHANNEL,
            Defaults::LANGUAGE_SYSTEM,
            null,
            null,
            true
        );

        $this->messageBusMock->expects(static::once())
            ->method('dispatch')
            ->willReturn(new Envelope($message));

        $this->sitemapHandler->run();
    }

    public function testSkipNonStorefrontSalesChannels(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM sales_channel');

        $storefrontId = Uuid::randomHex();
        $this->createSalesChannel([
            'id' => $storefrontId,
            'name' => 'storefront',
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'domains' => [[
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'http://valid.test',
            ]],
        ]);
        $this->createSalesChannel([
            'name' => 'api',
            'typeId' => Defaults::SALES_CHANNEL_TYPE_API,
            'domains' => [[
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'http://api.test',
            ]],
        ]);
        $this->createSalesChannel([
            'name' => 'export',
            'typeId' => Defaults::SALES_CHANNEL_TYPE_PRODUCT_COMPARISON,
            'domains' => [[
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'http://export.test',
            ]],
        ]);

        $message = new SitemapMessage(
            $storefrontId,
            Defaults::LANGUAGE_SYSTEM,
            null,
            null,
            false
        );

        $this->messageBusMock->expects(static::once())
            ->method('dispatch')
            ->with($message)
            ->willReturn(new Envelope($message));

        $this->sitemapHandler->run();
    }
}
