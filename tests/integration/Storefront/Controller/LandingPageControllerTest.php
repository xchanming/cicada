<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Storefront\Page\LandingPage\LandingPageLoadedHook;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;

/**
 * @internal
 */
#[Package('buyers-experience')]
class LandingPageControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->createData();
    }

    public function testLandingPageLoadedHookScriptsAreExecuted(): void
    {
        $response = $this->request('GET', '/myUrl', []);

        static::assertEquals(200, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(LandingPageLoadedHook::HOOK_NAME, $traces);
    }

    private function createData(): void
    {
        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = static::getContainer()->get('sales_channel.repository')->search(
            (
                new Criteria())->addFilter(
                    new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT),
                    new EqualsFilter('domains.url', $_SERVER['APP_URL'])
                ),
            Context::createDefaultContext()
        )->first();

        $data = [
            'id' => $this->ids->create('landing-page'),
            'name' => 'Test',
            'url' => 'myUrl',
            'active' => true,
            'salesChannels' => [
                [
                    'id' => $salesChannel->getId(),
                ],
            ],
            'cmsPage' => [
                'id' => $this->ids->create('cms-page'),
                'type' => 'product_list',
                'sections' => [
                    [
                        'position' => 0,
                        'type' => 'sidebar',
                        'blocks' => [
                            [
                                'type' => 'product-listing',
                                'position' => 1,
                                'slots' => [
                                    ['type' => 'product-listing', 'slot' => 'content'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        static::getContainer()->get('landing_page.repository')
            ->create([$data], Context::createDefaultContext());
    }
}
