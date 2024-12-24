<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Sitemap\Command;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Sitemap\Commands\SitemapGenerateCommand;
use Cicada\Core\Content\Sitemap\Service\SitemapExporter;
use Cicada\Core\Content\Sitemap\Struct\SitemapGenerationResult;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[Package('services-settings')]
class SitemapGenerateCommandTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    private MockObject&SitemapExporter $exporter;

    private SitemapGenerateCommand $command;

    protected function setUp(): void
    {
        $this->exporter = $this->createMock(SitemapExporter::class);

        $this->command = new SitemapGenerateCommand(
            static::getContainer()->get('sales_channel.repository'),
            $this->exporter,
            static::getContainer()->get(SalesChannelContextFactory::class),
            $this->createMock(EventDispatcher::class)
        );
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

        $result = new SitemapGenerationResult(true, null, null, $storefrontId, Defaults::LANGUAGE_SYSTEM);

        $this->exporter->expects(static::once())
            ->method('generate')
            ->with(static::callback(function (SalesChannelContext $context) use ($storefrontId) {
                static::assertSame($storefrontId, $context->getSalesChannelId());

                return true;
            }))
            ->willReturn($result);

        $input = new ArrayInput([]);
        $this->command->run($input, new NullOutput());
    }
}
