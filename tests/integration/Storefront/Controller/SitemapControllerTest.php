<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Page\Sitemap\SitemapPageLoadedHook;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class SitemapControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;

    public function testSitemapPageLoadedHookScriptsAreExecuted(): void
    {
        $response = $this->request('GET', '/sitemap.xml', []);
        static::assertEquals(200, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(SitemapPageLoadedHook::HOOK_NAME, $traces);
    }
}
