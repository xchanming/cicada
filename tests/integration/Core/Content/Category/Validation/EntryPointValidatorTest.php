<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Category\Validation;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class EntryPointValidatorTest extends TestCase
{
    use BasicTestDataBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<CategoryCollection>
     */
    private EntityRepository $categoryRepository;

    /**
     * @var EntityRepository<SalesChannelCollection>
     */
    private EntityRepository $salesChannelRepository;

    protected function setUp(): void
    {
        $this->categoryRepository = static::getContainer()->get(\sprintf('%s.repository', CategoryDefinition::ENTITY_NAME));
        $this->salesChannelRepository = static::getContainer()->get(\sprintf('%s.repository', SalesChannelDefinition::ENTITY_NAME));
    }

    public function testChangeNavigationFail(): void
    {
        $context = Context::createDefaultContext();
        $categoryId = $this->getValidCategoryId();
        $this->salesChannelRepository->update([
            [
                'id' => TestDefaults::SALES_CHANNEL,
                'navigationCategoryId' => $categoryId,
            ],
        ], $context);

        $this->expectException(WriteException::class);
        $this->categoryRepository->update([
            [
                'id' => $categoryId,
                'type' => CategoryDefinition::TYPE_LINK,
            ],
        ], $context);
    }

    public function testChangeServiceFail(): void
    {
        $context = Context::createDefaultContext();
        $categoryId = $this->getValidCategoryId();

        $this->expectException(WriteException::class);
        $this->salesChannelRepository->update([
            [
                'id' => TestDefaults::SALES_CHANNEL,
                'serviceCategory' => [
                    'id' => $categoryId,
                    'type' => CategoryDefinition::TYPE_LINK,
                ],
            ],
        ], $context);
    }

    public function testChangeFooterValid(): void
    {
        $context = Context::createDefaultContext();
        $categoryId = $this->getValidCategoryId();
        $this->salesChannelRepository->update([
            [
                'id' => TestDefaults::SALES_CHANNEL,
                'footerCategory' => [
                    'id' => $categoryId,
                    'type' => CategoryDefinition::TYPE_PAGE,
                ],
            ],
        ], $context);

        /** @var CategoryEntity|null $category */
        $category = $this->categoryRepository->search(new Criteria([$categoryId]), $context)->first();
        static::assertNotNull($category);
        static::assertSame(CategoryDefinition::TYPE_PAGE, $category->getType());
    }
}
