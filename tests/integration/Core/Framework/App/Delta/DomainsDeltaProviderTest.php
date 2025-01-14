<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Delta;

use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\Delta\DomainsDeltaProvider;
use Cicada\Core\Framework\App\Lifecycle\AbstractAppLifecycle;
use Cicada\Core\Framework\App\Lifecycle\AppLifecycle;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class DomainsDeltaProviderTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetName(): void
    {
        static::assertSame('domains', (new DomainsDeltaProvider())->getDeltaName());
    }

    public function testGetDomainsDelta(): void
    {
        $context = Context::createDefaultContext();
        $manifest = $this->getTestManifest();

        $this->getAppLifecycle()->install($manifest, false, $context);

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('name', 'test'))
            ->addAssociation('acl_role');

        $app = $this->getAppRepository()->search($criteria, $context)->getEntities()->first();
        static::assertNotNull($app);

        // Modify the existing privileges to get a delta
        $app->setAllowedHosts([]);

        $delta = (new DomainsDeltaProvider())->getReport($manifest, $app);

        static::assertCount(8, $delta);
        static::assertEquals([
            'my.app.com',
            'test.com',
            'base-url.com',
            'main-module',
            'swag-test.com',
            'payment.app',
            'tax-provider.app',
            'tax-provider-2.app',
        ], $delta);
    }

    public function testHasDelta(): void
    {
        $context = Context::createDefaultContext();
        $manifest = $this->getTestManifest();

        $this->getAppLifecycle()->install($manifest, false, $context);

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('name', 'test'));

        $app = $this->getAppRepository()->search($criteria, $context)->getEntities()->first();
        static::assertNotNull($app);

        static::assertFalse((new DomainsDeltaProvider())->hasDelta($manifest, $app));

        $app->setAllowedHosts([]);

        static::assertTrue((new DomainsDeltaProvider())->hasDelta($manifest, $app));
    }

    private function getAppLifecycle(): AbstractAppLifecycle
    {
        return static::getContainer()->get(AppLifecycle::class);
    }

    /**
     * @return EntityRepository<AppCollection>
     */
    private function getAppRepository(): EntityRepository
    {
        return static::getContainer()->get('app.repository');
    }

    private function getTestManifest(): Manifest
    {
        return Manifest::createFromXmlFile(__DIR__ . '/../Manifest/_fixtures/test/manifest.xml');
    }
}
