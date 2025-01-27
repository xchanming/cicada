<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Storefront\Page\Cms\CmsPageLoadedHook;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
class CmsControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->createData();
    }

    public function testCmsPageLoadedHookScriptsAreExecuted(): void
    {
        $response = $this->request('GET', '/widgets/cms/' . $this->ids->get('page'), []);
        static::assertEquals(200, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(CmsPageLoadedHook::HOOK_NAME, $traces);
    }

    public function testCmsPageLoadedHookScriptsAreExecutedForFullPage(): void
    {
        $response = $this->request('GET', '/page/cms/' . $this->ids->get('page'), []);
        static::assertEquals(200, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(CmsPageLoadedHook::HOOK_NAME, $traces);
    }

    public function testCmsPageLoadedHookScriptsAreExecutedForCategory(): void
    {
        $response = $this->request('GET', '/widgets/cms/navigation/' . $this->ids->get('category'), []);
        static::assertEquals(200, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(CmsPageLoadedHook::HOOK_NAME, $traces);
    }

    private function createData(): void
    {
        $category = [
            'id' => $this->ids->create('category'),
            'name' => 'Test',
            'type' => 'landing_page',
            'cmsPage' => [
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
            ],
        ];

        static::getContainer()->get('category.repository')->create([$category], Context::createDefaultContext());
    }
}
