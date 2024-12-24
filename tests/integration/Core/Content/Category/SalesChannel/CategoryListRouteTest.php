<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Category\SalesChannel;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Group('store-api')]
class CategoryListRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use QueueTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
        static::getContainer()->get(Connection::class)->executeStatement('SET FOREIGN_KEY_CHECKS = 0;');
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM category');
        static::getContainer()->get(Connection::class)->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');

        $this->createData();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
            'navigationCategoryId' => $this->ids->get('category'),
        ]);
    }

    public function testFetchAll(): void
    {
        $this->browser->request(
            'GET',
            '/store-api/category'
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(3, $response['total']);
        static::assertCount(3, $response['elements']);
        static::assertSame('category', $response['elements'][0]['apiAlias']);
        static::assertContains('Test', array_column($response['elements'], 'name'));
        static::assertContains('Test2', array_column($response['elements'], 'name'));
        static::assertContains('Test3', array_column($response['elements'], 'name'));
    }

    public function testLimit(): void
    {
        $this->browser->request(
            'GET',
            '/store-api/category?limit=1'
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(1, $response['total']);
        static::assertCount(1, $response['elements']);
    }

    public function testIds(): void
    {
        $this->browser->request(
            'POST',
            '/store-api/category',
            [
                'ids' => [
                    $this->ids->get('category'),
                ],
            ]
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(1, $response['total']);
        static::assertCount(1, $response['elements']);
        static::assertSame($this->ids->get('category'), $response['elements'][0]['id']);
    }

    public function testIncludes(): void
    {
        $this->browser->request(
            'POST',
            '/store-api/category',
            [
                'includes' => [
                    'category' => ['id', 'name'],
                ],
            ]
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(3, $response['total']);
        static::assertCount(3, $response['elements']);
        static::assertArrayHasKey('id', $response['elements'][0]);
        static::assertArrayHasKey('name', $response['elements'][0]);
        static::assertArrayNotHasKey('parentId', $response['elements'][0]);
    }

    private function createData(): void
    {
        $data = [
            [
                'id' => $this->ids->create('category'),
                'name' => 'Test',
                'active' => true,
                'children' => [
                    [
                        'id' => $this->ids->create('category2'),
                        'name' => 'Test2',
                        'active' => false,
                    ],
                    [
                        'id' => $this->ids->create('category3'),
                        'name' => 'Test3',
                        'active' => true,
                    ],
                ],
            ],
            [
                'id' => $this->ids->create('category4'),
                'name' => 'Out of scope, should not be in response',
                'active' => true,
            ],
        ];

        static::getContainer()->get('category.repository')
            ->create($data, Context::createDefaultContext());
    }
}
