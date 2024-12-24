<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Cms\Subscriber;

use Cicada\Core\Content\Cms\CmsException;
use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Content\Cms\Subscriber\CmsPageDefaultChangeSubscriber;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(CmsPageDefaultChangeSubscriber::class)]
class CmsPageBeforeDefaultChangeSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $cmsPageRepository;

    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cmsPageRepository = static::getContainer()->get('cms_page.repository');
        $this->systemConfigService = static::getContainer()->get(SystemConfigService::class);
    }

    #[DataProvider('validDefaultCmsPageDataProvider')]
    public function testSetDefaultDoesNotThrow(string $validCmsPageId, ?string $salesChannelId): void
    {
        $this->createCmsPage($validCmsPageId);

        $this->systemConfigService->set(ProductDefinition::CONFIG_KEY_DEFAULT_CMS_PAGE_PRODUCT, $validCmsPageId, $salesChannelId);
    }

    public static function validDefaultCmsPageDataProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'validCmsPageId with salesChanelId null' => [
            'validCmsPageId' => $ids->get('validCmsPageId'),
            'salesChannelId' => null,
        ];

        yield 'validCmsPageId with default salesChanelId' => [
            'validCmsPageId' => $ids->get('validCmsPageId'),
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
        ];
    }

    #[DataProvider('invalidDefaultCmsPageDataProvider')]
    public function testSetInvalidDefaultThrow(string $invalidCmsPageId, ?string $salesChannelId): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->systemConfigService->set(ProductDefinition::CONFIG_KEY_DEFAULT_CMS_PAGE_PRODUCT, $invalidCmsPageId, $salesChannelId);
    }

    public static function invalidDefaultCmsPageDataProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'invalidCmsPageId with salesChanelId null' => [
            'invalidCmsPageId' => $ids->get('invalidCmsPageId'),
            'salesChannelId' => null,
        ];

        yield 'invalidCmsPageId with default salesChanelId' => [
            'invalidCmsPageId' => $ids->get('invalidCmsPageId'),
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
        ];
    }

    public function testDeleteSalesChannelDefaultDoesNotThrow(): void
    {
        $cmsPage = Uuid::randomHex();
        $this->createCmsPage($cmsPage);

        // set sales channel specific default
        $this->systemConfigService->set(ProductDefinition::CONFIG_KEY_DEFAULT_CMS_PAGE_PRODUCT, $cmsPage, TestDefaults::SALES_CHANNEL);

        // expect to be able to delete the default
        $this->systemConfigService->set(ProductDefinition::CONFIG_KEY_DEFAULT_CMS_PAGE_PRODUCT, null, TestDefaults::SALES_CHANNEL);
    }

    public function testDeleteOverallDefaultThrow(): void
    {
        $cmsPage = Uuid::randomHex();
        $exceptionWasThrown = false;
        $this->createCmsPage($cmsPage);

        // set overall default
        $this->systemConfigService->set(ProductDefinition::CONFIG_KEY_DEFAULT_CMS_PAGE_PRODUCT, $cmsPage);

        try {
            $this->systemConfigService->set(ProductDefinition::CONFIG_KEY_DEFAULT_CMS_PAGE_PRODUCT, null);
        } catch (CmsException $exception) {
            static::assertEquals(CmsException::OVERALL_DEFAULT_SYSTEM_CONFIG_DELETION_CODE, $exception->getErrorCode());
            $exceptionWasThrown = true;
        } finally {
            if (!$exceptionWasThrown) {
                static::fail('Expected exception with error code ' . CmsException::OVERALL_DEFAULT_SYSTEM_CONFIG_DELETION_CODE . ' to be thrown.');
            }
        }
    }

    private function createCmsPage(string $cmsPageId): void
    {
        $cmsPage = [
            'id' => $cmsPageId,
            'name' => 'test page',
            'type' => 'product_detail',
        ];

        $this->cmsPageRepository->create([$cmsPage], Context::createDefaultContext());
    }
}
