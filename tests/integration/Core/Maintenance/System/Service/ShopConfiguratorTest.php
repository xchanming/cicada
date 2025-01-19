<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Maintenance\System\Service;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Maintenance\System\Service\ShopConfigurator;
use Cicada\Core\System\Currency\CurrencyCollection;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
class ShopConfiguratorTest extends TestCase
{
    use CacheTestBehaviour;
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private ShopConfigurator $shopConfigurator;

    private SystemConfigService $systemConfigService;

    /**
     * @var EntityRepository<LanguageCollection>
     */
    private EntityRepository $langRepo;

    /**
     * @var EntityRepository<CurrencyCollection>
     */
    private EntityRepository $currencyRepo;

    protected function setUp(): void
    {
        $this->shopConfigurator = static::getContainer()->get(ShopConfigurator::class);
        $this->systemConfigService = static::getContainer()->get(SystemConfigService::class);
        $this->langRepo = static::getContainer()->get('language.repository');
        $this->currencyRepo = static::getContainer()->get('currency.repository');
    }

    public function testUpdateBasicInformation(): void
    {
        $this->shopConfigurator->updateBasicInformation('test-shop', 'shop@test.com');

        static::assertSame('test-shop', $this->systemConfigService->get('core.basicInformation.shopName'));
        static::assertSame('shop@test.com', $this->systemConfigService->get('core.basicInformation.email'));
    }

    public function testSwitchLanguageWithNewLanguage(): void
    {
        $this->shopConfigurator->setDefaultLanguage('en-US');

        $lang = $this->langRepo->search(new Criteria([Defaults::LANGUAGE_SYSTEM]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($lang);
        static::assertSame('English (US)', $lang->getName());
    }

    public function testSwitchLanguageWithDefaultLocale(): void
    {
        $this->shopConfigurator->setDefaultLanguage('en-GB');

        $lang = $this->langRepo->search(new Criteria([Defaults::LANGUAGE_SYSTEM]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($lang);
        static::assertSame('English', $lang->getName());
    }

    public function testSwitchLanguageWithExistingLanguage(): void
    {
        $this->shopConfigurator->setDefaultLanguage('zh-CN');

        $lang = $this->langRepo->search(new Criteria([Defaults::LANGUAGE_SYSTEM]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($lang);
        static::assertSame('中文', $lang->getName());
    }

    public function testSwitchDefaultCurrencyWithNewCurrency(): void
    {
        $this->shopConfigurator->setDefaultCurrency('RUB');

        $currency = $this->currencyRepo->search(new Criteria([Defaults::CURRENCY]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($currency);
        static::assertSame('RUB', $currency->getSymbol());
        static::assertSame('俄罗斯卢布', $currency->getName());
        static::assertSame('RUB', $currency->getShortName());
        static::assertSame('RUB', $currency->getIsoCode());
        static::assertSame(1.0, $currency->getFactor());
        static::assertSame(2, $currency->getItemRounding()->getDecimals());
        static::assertSame(0.01, $currency->getItemRounding()->getInterval());
        static::assertTrue($currency->getItemRounding()->roundForNet());
        static::assertSame(2, $currency->getTotalRounding()->getDecimals());
        static::assertSame(0.01, $currency->getTotalRounding()->getInterval());
        static::assertTrue($currency->getTotalRounding()->roundForNet());
    }

    public function testSwitchDefaultCurrencyWithDefaultCurrency(): void
    {
        $this->shopConfigurator->setDefaultCurrency('EUR');

        $currency = $this->currencyRepo->search(new Criteria([Defaults::CURRENCY]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($currency);
        static::assertSame('欧元', $currency->getName());
    }

    public function testSwitchDefaultCurrencyWithExistingCurrency(): void
    {
        $this->shopConfigurator->setDefaultCurrency('GBP');

        $currency = $this->currencyRepo->search(new Criteria([Defaults::CURRENCY]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($currency);
        static::assertSame('英镑', $currency->getName());
        static::assertSame(1.0, $currency->getFactor());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', 'CNY'));

        $oldDefault = $this->currencyRepo->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($oldDefault);
        static::assertSame('人民币', $oldDefault->getName());
        static::assertSame(1.0, $oldDefault->getFactor());
    }
}
