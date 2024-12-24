<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\Subscriber;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\ImportExport\Event\ImportExportBeforeImportRecordEvent;
use Cicada\Core\Content\ImportExport\Event\Subscriber\ProductCategoryPathsSubscriber;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Api\Sync\SyncService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('services-settings')]
class ProductCategoryPathsSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $categoryRepository;

    protected function setUp(): void
    {
        $this->categoryRepository = static::getContainer()->get('category.repository');
    }

    /**
     * @return array<string, mixed>
     */
    public static function provideCategoryPaths(): array
    {
        return [
            '2 Layer assignment' => [
                'categoriesToWrite' => [
                    [
                        'id' => Uuid::fromStringToHex('home'),
                        'name' => 'HomeCategory',
                    ],
                    [
                        'id' => Uuid::fromStringToHex('subCat'),
                        'name' => 'Sub',
                        'parentId' => Uuid::fromStringToHex('home'),
                    ],
                ],

                'record' => [
                    'categories' => [],
                ],

                'row' => [
                    'category_paths' => 'HomeCategory>Sub',
                ],

                'assertion' => [
                    [
                        'id' => Uuid::fromStringToHex('subCat'),
                    ],
                ],
            ],

            '3 Layer assignment' => [
                'categoriesToWrite' => [
                    [
                        'id' => Uuid::fromStringToHex('home'),
                        'name' => 'HomeCategory',
                    ],
                    [
                        'id' => Uuid::fromStringToHex('subCat'),
                        'name' => 'Sub',
                        'parentId' => Uuid::fromStringToHex('home'),
                    ],
                    [
                        'id' => Uuid::fromStringToHex('subSubCat'),
                        'name' => 'SubSub',
                        'parentId' => Uuid::fromStringToHex('subCat'),
                    ],
                ],

                'record' => [
                    'categories' => [],
                ],

                'row' => [
                    'category_paths' => 'HomeCategory>Sub>SubSub',
                ],

                'assertion' => [
                    [
                        'id' => Uuid::fromStringToHex('subSubCat'),
                    ],
                ],
            ],

            'Multiple assignments' => [
                'categoriesToWrite' => [
                    [
                        'id' => Uuid::fromStringToHex('home'),
                        'name' => 'HomeCategory',
                    ],
                    [
                        'id' => Uuid::fromStringToHex('subCat'),
                        'name' => 'Sub',
                        'parentId' => Uuid::fromStringToHex('home'),
                    ],
                    [
                        'id' => Uuid::fromStringToHex('subSubCat'),
                        'name' => 'SubSub',
                        'parentId' => Uuid::fromStringToHex('subCat'),
                    ],
                ],

                'record' => [
                    'categories' => [],
                ],

                'row' => [
                    'category_paths' => implode('|', ['HomeCategory>Sub>SubSub', 'HomeCategory>Sub', 'HomeCategory']),
                ],

                'assertion' => [
                    [
                        'id' => Uuid::fromStringToHex('subSubCat'),
                    ],
                    [
                        'id' => Uuid::fromStringToHex('subCat'),
                    ],
                    [
                        'id' => Uuid::fromStringToHex('home'),
                    ],
                ],
            ],

            'Try to use own identifier, but category with name is found' => [
                'categoriesToWrite' => [
                    [
                        'id' => Uuid::fromStringToHex('ownIdentifierAsName'),
                        'name' => 'Category 1',
                    ],
                    [
                        'id' => Uuid::fromStringToHex('category2'),
                        'name' => 'ownIdentifierAsName',
                    ],
                    [
                        'id' => Uuid::fromStringToHex('category3'),
                        'name' => 'Category 3',
                    ],
                ],

                'record' => [
                    'categories' => [],
                ],

                'row' => [
                    'category_paths' => 'ownIdentifierAsName',
                ],

                'assertion' => [
                    [
                        'id' => Uuid::fromStringToHex('category2'),
                    ],
                ],
            ],

            'With invalid root' => [
                'categoriesToWrite' => [],

                'record' => [
                    'categories' => [],
                ],

                'row' => [
                    'category_paths' => 'DoesNotExist>Sub>SubSub',
                ],

                'assertion' => [],
            ],

            'Use new categories in path' => [
                'categoriesToWrite' => [
                    [
                        'id' => Uuid::fromStringToHex('home'),
                        'name' => 'HomeCategory',
                    ],
                ],

                'record' => [
                    'categories' => [],
                ],

                'row' => [
                    'category_paths' => 'HomeCategory>Sub>SubSub',
                ],

                'assertion' => [
                    [
                        'id' => Uuid::fromStringToHex('HomeCategory>Sub>SubSub'),
                    ],
                ],
            ],

            'Use new categories mixed with existing IDs' => [
                'categoriesToWrite' => [
                    [
                        'id' => Uuid::fromStringToHex('home'),
                        'name' => 'HomeCategory',
                    ],
                ],

                'record' => [
                    'categories' => [
                        [
                            'id' => Uuid::fromStringToHex('existingId'),
                        ],
                    ],
                ],

                'row' => [
                    'category_paths' => 'HomeCategory>Sub>SubSub',
                ],

                'assertion' => [
                    [
                        'id' => Uuid::fromStringToHex('existingId'),
                    ],
                    [
                        'id' => Uuid::fromStringToHex('HomeCategory>Sub>SubSub'),
                    ],
                ],
            ],

            'Use new categories in multiple paths' => [
                'categoriesToWrite' => [
                    [
                        'id' => Uuid::fromStringToHex('home'),
                        'name' => 'HomeCategory',
                    ],
                ],

                'record' => [
                    'categories' => [],
                ],

                'row' => [
                    'category_paths' => 'HomeCategory>Sub>SubSub|HomeCategory>Sub>NewSub>NewSubSub',
                ],

                'assertion' => [
                    [
                        'id' => Uuid::fromStringToHex('HomeCategory>Sub>SubSub'),
                    ],
                    [
                        'id' => Uuid::fromStringToHex('HomeCategory>Sub>NewSub>NewSubSub'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<array<string, mixed|null>> $categoriesToWrite
     * @param array<array<string, mixed|null>> $record
     * @param array<array<string>> $row
     * @param array<array<string>> $assertion
     */
    #[DataProvider('provideCategoryPaths')]
    public function testCategoryPathToAssignment(array $categoriesToWrite, array $record, array $row, array $assertion): void
    {
        $context = Context::createDefaultContext();

        $this->categoryRepository->upsert($categoriesToWrite, $context);

        $event = new ImportExportBeforeImportRecordEvent($record, $row, new Config([], ['sourceEntity' => ProductDefinition::ENTITY_NAME], []), $context);

        $subscriber = new ProductCategoryPathsSubscriber($this->categoryRepository, static::getContainer()->get(SyncService::class));

        $subscriber->categoryPathsToAssignment($event);

        static::assertSame($assertion, $event->getRecord()['categories']);
    }
}
