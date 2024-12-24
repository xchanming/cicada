<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\Command;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Cms\CmsPageCollection;
use Cicada\Core\Content\Cms\CmsPageDefinition;
use Cicada\Core\Content\Cms\Command\CreatePageCommand;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(CreatePageCommand::class)]
class CreatePageCommandTest extends TestCase
{
    /**
     * @var StaticEntityRepository<CmsPageCollection>
     */
    private StaticEntityRepository $cmsPageRepository;

    private CreatePageCommand $command;

    protected function setUp(): void
    {
        $productRepository = new StaticEntityRepository([
            [
                'product-id-1',
                'product-id-2',
            ],
        ], new ProductDefinition());

        $categoryRepository = new StaticEntityRepository([
            [
                'category-id-1',
            ],
        ], new CategoryDefinition());

        $mediaRepository = new StaticEntityRepository([
            [
                'media-id-1',
            ],
        ], new MediaDefinition());

        $this->cmsPageRepository = new StaticEntityRepository([], new CmsPageDefinition());

        $this->command = new CreatePageCommand(
            $this->cmsPageRepository,
            $productRepository,
            $categoryRepository,
            $mediaRepository
        );
    }

    public function testCreatePageWithoutResetOption(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $cmsPages = array_shift($this->cmsPageRepository->creates);
        static::assertIsArray($cmsPages);
        static::assertCount(1, $cmsPages);

        $cmsPage = array_shift($cmsPages);

        static::assertSame('landing_page', $cmsPage['type']);
        static::assertCount(4, $cmsPage['blocks']);

        // no deleted cms pages
        static::assertEmpty($this->cmsPageRepository->deletes);

        static::assertSame(0, $commandTester->getStatusCode());
    }

    public function testCreatePageAndResetAllCmsPagesBefore(): void
    {
        $this->cmsPageRepository->addSearch([
            'deleted-page-id-1',
            'deleted-page-id-2',
            'deleted-page-id-3',
        ]);

        $commandTester = new CommandTester($this->command);

        $commandTester->execute([
            '--reset' => true,
        ]);

        $cmsPages = array_shift($this->cmsPageRepository->creates);
        static::assertIsArray($cmsPages);
        static::assertCount(1, $cmsPages);

        $cmsPage = array_shift($cmsPages);

        static::assertSame('landing_page', $cmsPage['type']);
        static::assertCount(4, $cmsPage['blocks']);

        static::assertEquals([[
            ['id' => 'deleted-page-id-1'],
            ['id' => 'deleted-page-id-2'],
            ['id' => 'deleted-page-id-3'],
        ]], $this->cmsPageRepository->deletes);

        static::assertSame(0, $commandTester->getStatusCode());
    }
}
