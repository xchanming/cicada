<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Sitemap\Provider;

use Cicada\Core\Content\Sitemap\Provider\HomeUrlProvider;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
class HomeUrlProviderTest extends TestCase
{
    use IntegrationTestBehaviour;

    private SalesChannelContext $salesChannelContext;

    /**
     * @var EntityRepository<LanguageCollection>
     */
    private EntityRepository $languageRepository;

    protected function setUp(): void
    {
        $this->languageRepository = static::getContainer()->get('language.repository');
        $contextFactory = static::getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create('', TestDefaults::SALES_CHANNEL);
    }

    public function testGetHomeUrlSalesChannelIsExistingTwoDomain(): void
    {
        $criteria = new Criteria();
        $criteria->addAssociation('locale');
        $languages = $this->languageRepository->search($criteria, $this->salesChannelContext->getContext())
            ->getEntities();

        $domain = new SalesChannelDomainEntity();
        $domain->setId(Uuid::randomHex());
        $domain->setUrl('https://test-sitemap.de');
        $domain->setHreflangUseOnlyLocale(false);
        $first = $languages->first();
        static::assertInstanceOf(LanguageEntity::class, $first);
        $domain->setLanguageId($first->getId());

        static::assertInstanceOf(SalesChannelDomainCollection::class, $this->salesChannelContext->getSalesChannel()->getDomains());
        $this->salesChannelContext->getSalesChannel()->getDomains()->add($domain);

        $domain = new SalesChannelDomainEntity();
        $domain->setId(Uuid::randomHex());
        $domain->setUrl('https://test-sitemap.de/en');
        $domain->setHreflangUseOnlyLocale(false);
        $last = $languages->last();
        static::assertInstanceOf(LanguageEntity::class, $last);
        $domain->setLanguageId($last->getId());

        $this->salesChannelContext->getSalesChannel()->getDomains()->add($domain);

        $homeUrlProvider = new HomeUrlProvider();

        static::assertCount(1, $homeUrlProvider->getUrls($this->salesChannelContext, 100)->getUrls());
    }

    public function testGetHomeUrlWithSalesChannelIsExistingOneDomain(): void
    {
        $criteria = new Criteria();
        $criteria->addAssociation('locale');
        $languages = $this->languageRepository->search($criteria, $this->salesChannelContext->getContext())
            ->getEntities();

        $languageId = $this->salesChannelContext->getLanguageId();
        $language = $languages->get($languageId);
        static::assertInstanceOf(LanguageEntity::class, $language);

        $domain = new SalesChannelDomainEntity();
        $domain->setId(Uuid::randomHex());
        $domain->setUrl('https://test-sitemap.de/en');
        $domain->setHreflangUseOnlyLocale(false);
        $domain->setLanguageId($language->getId());

        static::assertInstanceOf(SalesChannelDomainCollection::class, $this->salesChannelContext->getSalesChannel()->getDomains());
        $this->salesChannelContext->getSalesChannel()->getDomains()->add($domain);

        $homeUrlProvider = new HomeUrlProvider();

        static::assertCount(1, $homeUrlProvider->getUrls($this->salesChannelContext, 100)->getUrls());
    }

    public function testGetHomeUrlWithSalesChannelHaveNoDomain(): void
    {
        $homeUrlProvider = new HomeUrlProvider();

        $results = $homeUrlProvider->getUrls($this->salesChannelContext, 100);

        static::assertEmpty($results->getUrls()[0]->getLoc());
    }

    public function testProviderNameIsHome(): void
    {
        $homeUrlProvider = new HomeUrlProvider();

        static::assertEquals('home', $homeUrlProvider->getName());
    }
}
