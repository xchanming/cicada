<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Sitemap\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Package('services-settings')]
#[Group('store-api')]
class SitemapRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
    }

    public function testEmpty(): void
    {
        $this->browser->request('POST', '/store-api/sitemap');

        static::assertNotFalse($this->browser->getResponse()->getContent());

        static::assertIsString($this->browser->getResponse()->getContent());
        $response = json_decode($this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());
        static::assertCount(0, $response);
    }

    public function testSitemapListsEntries(): void
    {
        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create('', $this->ids->get('sales-channel'));

        $fs = static::getContainer()->get('cicada.filesystem.sitemap');
        $fs->write('sitemap/salesChannel-' . $context->getSalesChannel()->getId() . '-' . $context->getLanguageId() . '/test.xml', 'some content');

        $this->browser->request('POST', '/store-api/sitemap');

        static::assertNotFalse($this->browser->getResponse()->getContent());

        static::assertIsString($this->browser->getResponse()->getContent());
        $response = json_decode($this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());
        static::assertCount(1, $response);
        static::assertSame('sitemap', $response[0]['apiAlias']);
        static::assertArrayHasKey('filename', $response[0]);
        static::assertArrayHasKey('created', $response[0]);
        static::assertNotEmpty($response[0]['filename']);
        static::assertNotEmpty($response[0]['created']);
    }
}
