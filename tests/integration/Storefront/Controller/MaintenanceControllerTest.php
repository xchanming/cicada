<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Cicada\Core\Defaults;
use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Storefront\Page\Maintenance\MaintenancePageLoadedHook;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class MaintenanceControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->createData();
    }

    public function testMaintenancePageLoadedHookScriptsAreExecuted(): void
    {
        $this->setMaintenanceMode();

        $browser = KernelLifecycleManager::createBrowser($this->getKernel());
        $browser->followRedirects();

        $browser->request('GET', EnvironmentHelper::getVariable('APP_URL') . '/');
        $response = $browser->getResponse();

        static::assertEquals(503, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(MaintenancePageLoadedHook::HOOK_NAME, $traces);
    }

    public function testMaintenancePageLoadedHookScriptsAreExecutedForSinglePage(): void
    {
        $response = $this->request('GET', '/maintenance/singlepage/' . $this->ids->get('page'), []);
        static::assertEquals(200, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(MaintenancePageLoadedHook::HOOK_NAME, $traces);
    }

    private function createData(): void
    {
        $page = [
            'id' => $this->ids->create('page'),
            'name' => 'test page',
            'type' => 'landingpage',
            'sections' => [
                [
                    'id' => $this->ids->create('section'),
                    'type' => 'default',
                    'position' => 0,
                    'blocks' => [
                        [
                            'type' => 'text',
                            'position' => 0,
                            'slots' => [
                                [
                                    'id' => $this->ids->create('slot1'),
                                    'type' => 'text',
                                    'slot' => 'content',
                                    'config' => [
                                        'content' => [
                                            'source' => 'static',
                                            'value' => 'initial',
                                        ],
                                    ],
                                ],
                                [
                                    'id' => $this->ids->create('slot2'),
                                    'type' => 'text',
                                    'slot' => 'content',
                                    'config' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        static::getContainer()->get('cms_page.repository')->create([$page], Context::createDefaultContext());
    }

    private function setMaintenanceMode(): void
    {
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = static::getContainer()->get('sales_channel.repository');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $salesChannelRepository->search(
            (new Criteria())->addFilter(
                new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT),
                new EqualsFilter('domains.url', $_SERVER['APP_URL'])
            ),
            Context::createDefaultContext()
        )->first();

        $salesChannelRepository->update([
            [
                'id' => $salesChannel->getId(),
                'maintenance' => true,
            ],
        ], Context::createDefaultContext());

        static::getContainer()->get(SystemConfigService::class)->set('core.basicInformation.maintenancePage', $this->ids->get('page'));
    }
}
