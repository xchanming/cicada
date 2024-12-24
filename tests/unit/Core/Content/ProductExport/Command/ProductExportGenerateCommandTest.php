<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ProductExport\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\ProductExport\Command\ProductExportGenerateCommand;
use Cicada\Core\Content\ProductExport\ProductExportException;
use Cicada\Core\Content\ProductExport\Service\ProductExporterInterface;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversClass(ProductExportGenerateCommand::class)]
class ProductExportGenerateCommandTest extends TestCase
{
    private CommandTester $commandTester;

    private ProductExporterInterface&MockObject $productExporter;

    private AbstractSalesChannelContextFactory&MockObject $salesChannelContextFactory;

    protected function setUp(): void
    {
        $this->salesChannelContextFactory = $this->createMock(AbstractSalesChannelContextFactory::class);
        $this->productExporter = $this->createMock(ProductExporterInterface::class);
        $command = new ProductExportGenerateCommand($this->salesChannelContextFactory, $this->productExporter);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecutionWithInvalidSalesChannel(): void
    {
        $salesChannelId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getContext')->willReturn($context);

        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setId($salesChannelId);
        $salesChannelEntity->setTypeId(Defaults::SALES_CHANNEL_TYPE_PRODUCT_COMPARISON);
        $salesChannelContext->method('getSalesChannel')->willReturn($salesChannelEntity);

        $this->salesChannelContextFactory->method('create')->willReturn($salesChannelContext);

        static::expectException(ProductExportException::class);
        static::expectExceptionMessage('Only sales channels from type "Storefront" can be used for exports.');

        $this->commandTester->execute([
            'sales-channel-id' => $salesChannelId,
        ]);
    }

    public function testExecuteWithValidData(): void
    {
        $salesChannelId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getContext')->willReturn($context);

        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setId($salesChannelId);
        $salesChannelEntity->setTypeId(Defaults::SALES_CHANNEL_TYPE_STOREFRONT);
        $salesChannelContext->method('getSalesChannel')->willReturn($salesChannelEntity);

        $this->salesChannelContextFactory->method('create')->willReturn($salesChannelContext);

        $this->productExporter->expects(static::once())->method('export');

        $this->commandTester->execute([
            'sales-channel-id' => $salesChannelId,
            '--force' => false,
            '--include-inactive' => true,
        ]);

        static::assertSame(0, $this->commandTester->getStatusCode());
    }
}
