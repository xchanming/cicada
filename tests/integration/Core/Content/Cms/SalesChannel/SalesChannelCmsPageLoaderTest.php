<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Cms\SalesChannel;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Cicada\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoader;
use Cicada\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class SalesChannelCmsPageLoaderTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @var EntityRepository<CategoryCollection>
     */
    private EntityRepository $categoryRepository;

    private SalesChannelCmsPageLoaderInterface $salesChannelCmsPageLoader;

    private SalesChannelContext $salesChannelContext;

    private static string $firstSlotId;

    private static string $secondSlotId;

    private static string $categoryId;

    /**
     * @var array<string, mixed>
     */
    private static array $category;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$firstSlotId = Uuid::randomHex();
        self::$secondSlotId = Uuid::randomHex();
        self::$categoryId = Uuid::randomHex();

        self::$category = [
            'id' => self::$categoryId,
            'name' => 'test category',
            'cmsPage' => [
                'id' => Uuid::randomHex(),
                'name' => 'test page',
                'type' => 'landingpage',
                'sections' => [
                    [
                        'id' => Uuid::randomHex(),
                        'type' => 'default',
                        'position' => 0,
                        'blocks' => [
                            [
                                'type' => 'text',
                                'position' => 0,
                                'slots' => [
                                    [
                                        'id' => self::$firstSlotId,
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
                                        'id' => self::$secondSlotId,
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
            'slotConfig' => [
                self::$firstSlotId => [
                    'content' => [
                        'source' => 'static',
                        'value' => 'overwrittenByCategory',
                    ],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryRepository = static::getContainer()->get('category.repository');
        $this->salesChannelCmsPageLoader = static::getContainer()->get(SalesChannelCmsPageLoader::class);

        $this->salesChannelContext = $this->createSalesChannelContext();

        $this->categoryRepository->create([self::$category], $this->salesChannelContext->getContext());
    }

    public function testSlotOverwrite(): void
    {
        $pages = $this->salesChannelCmsPageLoader->load(
            new Request(),
            new Criteria([self::$category['cmsPage']['id']]),
            $this->salesChannelContext,
            []
        );

        static::assertEquals(1, $pages->getTotal());

        $page = $pages->getEntities()->first();
        static::assertNotNull($page);
        $sections = $page->getSections();
        static::assertNotNull($sections);
        $firstSection = $sections->first();
        static::assertNotNull($firstSection);
        $blocks = $firstSection->getBlocks();
        static::assertNotNull($blocks);
        $firstSlot = $blocks->getSlots()->get(self::$firstSlotId);
        static::assertNotNull($firstSlot);

        static::assertEquals(
            self::$category['cmsPage']['sections'][0]['blocks'][0]['slots'][0]['config'],
            $firstSlot->getConfig()
        );

        static::assertEquals(
            new FieldConfigCollection([new FieldConfig('content', 'static', 'initial')]),
            $firstSlot->getFieldConfig()
        );

        $secondSlot = $blocks->getSlots()->get(self::$secondSlotId);
        static::assertNotNull($secondSlot);
        static::assertNull($secondSlot->getConfig());

        // overwrite in category
        $customSlotConfig = [
            (string) self::$category['cmsPage']['sections'][0]['blocks'][0]['slots'][0]['id'] => [
                'content' => [
                    'source' => 'static',
                    'value' => 'overwrite',
                ],
            ],
            (string) self::$category['cmsPage']['sections'][0]['blocks'][0]['slots'][1]['id'] => [
                'content' => [
                    'source' => 'static',
                    'value' => 'overwrite',
                ],
            ],
        ];

        $pages = $this->salesChannelCmsPageLoader->load(
            new Request(),
            new Criteria([self::$category['cmsPage']['id']]),
            $this->salesChannelContext,
            $customSlotConfig
        );

        static::assertGreaterThanOrEqual(1, $pages->getTotal());

        $page = $pages->getEntities()->first();
        static::assertNotNull($page);
        $sections = $page->getSections();
        static::assertNotNull($sections);
        $firstSection = $sections->first();
        static::assertNotNull($firstSection);
        $blocks = $firstSection->getBlocks();
        static::assertNotNull($blocks);
        $firstSlot = $blocks->getSlots()->get(self::$firstSlotId);
        static::assertNotNull($firstSlot);

        static::assertEquals(
            $customSlotConfig[self::$category['cmsPage']['sections'][0]['blocks'][0]['slots'][0]['id']],
            $firstSlot->getConfig()
        );

        static::assertEquals(
            new FieldConfigCollection([new FieldConfig('content', 'static', 'overwrite')]),
            $firstSlot->getFieldConfig()
        );

        $secondSlot = $blocks->getSlots()->get(self::$secondSlotId);
        static::assertNotNull($secondSlot);
        static::assertEquals(
            $customSlotConfig[self::$category['cmsPage']['sections'][0]['blocks'][0]['slots'][1]['id']],
            $secondSlot->getConfig()
        );
    }

    public function testInheritSlotConfig(): void
    {
        $salesChannelContextDe = $this->createSalesChannelContext(
            [
                'languages' => [
                    ['id' => Defaults::LANGUAGE_SYSTEM],
                    ['id' => $this->getZhCnLanguageId()],
                ],
                'domains' => [
                    [
                        'languageId' => $this->getZhCnLanguageId(),
                        'currencyId' => Defaults::CURRENCY,
                        'snippetSetId' => $this->getSnippetSetIdForLocale('zh-CN'),
                        'url' => 'http://localhost/de',
                    ],
                ],
            ],
            [
                SalesChannelContextService::LANGUAGE_ID => $this->getZhCnLanguageId(),
            ]
        );

        $pages = $this->salesChannelCmsPageLoader->load(
            new Request(),
            new Criteria([self::$category['cmsPage']['id']]),
            $salesChannelContextDe
        );

        static::assertNotEmpty($pages->getEntities()->first()?->getSections()?->getBlocks()->getSlots()->get(self::$firstSlotId)?->getConfig());
    }

    public function testInheritSlotConfigOverwriteByCategory(): void
    {
        $salesChannelContextDe = $this->createSalesChannelContext(
            [
                'languages' => [
                    ['id' => Defaults::LANGUAGE_SYSTEM],
                    ['id' => $this->getZhCnLanguageId()],
                ],
                'domains' => [
                    [
                        'languageId' => $this->getZhCnLanguageId(),
                        'currencyId' => Defaults::CURRENCY,
                        'snippetSetId' => $this->getSnippetSetIdForLocale('zh-CN'),
                        'url' => 'http://localhost/de',
                    ],
                ],
            ],
            [
                SalesChannelContextService::LANGUAGE_ID => $this->getZhCnLanguageId(),
            ]
        );

        $criteria = new Criteria([self::$categoryId]);
        $criteria->addAssociation('media');

        $category = $this->categoryRepository->search($criteria, $salesChannelContextDe->getContext())->getEntities()->get(self::$categoryId);
        static::assertNotNull($category);

        $pages = $this->salesChannelCmsPageLoader->load(
            new Request(),
            new Criteria([self::$category['cmsPage']['id']]),
            $salesChannelContextDe,
            $category->getTranslation('slotConfig')
        );

        $config = $pages->getEntities()->first()?->getSections()?->getBlocks()->getSlots()->get(self::$firstSlotId)?->getConfig();
        static::assertIsArray($config);
        static::assertEquals('overwrittenByCategory', $config['content']['value']);
    }
}
