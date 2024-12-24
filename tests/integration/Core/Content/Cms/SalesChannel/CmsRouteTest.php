<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Cms\SalesChannel;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Group('store-api')]
class CmsRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->createData();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
    }

    public function test404Request(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/cms/e63dfd85345645068881959c0260a1a1',
                [
                ]
            );

        $content = $this->browser->getResponse()->getContent();
        static::assertIsString($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertSame('CONTENT__CMS_PAGE_NOT_FOUND', $response['errors'][0]['code']);
    }

    public function testResolvedPage(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/cms/' . $this->ids->get('page'),
                [
                ]
            );

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        $content = $this->browser->getResponse()->getContent();
        static::assertIsString($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame($this->ids->get('page'), $response['id']);
        static::assertSame('test page', $response['name']);
        static::assertSame('landingpage', $response['type']);
        static::assertNotEmpty($response['sections']);
        static::assertCount(1, $response['sections']);
        static::assertNotEmpty($response['sections'][0]['blocks']);
        static::assertCount(1, $response['sections'][0]['blocks']);
        static::assertCount(2, $response['sections'][0]['blocks'][0]['slots']);
    }

    private function createData(): void
    {
        $cms = [
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
        ];

        static::getContainer()->get('cms_page.repository')->create([$cms], Context::createDefaultContext());
    }
}
