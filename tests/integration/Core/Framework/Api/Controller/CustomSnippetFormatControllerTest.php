<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api\Controller;

use Cicada\Core\Framework\Plugin;
use Cicada\Core\Framework\Plugin\KernelPluginCollection;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class CustomSnippetFormatControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    public function testGetSnippetsWithoutPlugins(): void
    {
        $url = '/api/_action/custom-snippet';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);
        $content = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('data', $content);

        static::assertSame([
            'address/city',
            'address/company',
            'address/country',
            'address/country_state',
            'address/department',
            'address/name',
            'address/phone_number',
            'address/salutation',
            'address/street',
            'address/title',
            'address/zipcode',
            'symbol/comma',
            'symbol/dash',
            'symbol/tilde',
        ], $content['data']);
    }

    public function testGetSnippetsWithPlugins(): void
    {
        $plugin = new BundleWithCustomSnippet(true, __DIR__ . '/Fixtures/BundleWithCustomSnippet');
        $pluginCollection = static::getContainer()->get(KernelPluginCollection::class);
        $pluginCollection->add($plugin);

        $url = '/api/_action/custom-snippet';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);
        $content = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('data', $content);

        static::assertSame([
            'address/city',
            'address/company',
            'address/country',
            'address/country_state',
            'address/department',
            'address/name',
            'address/phone_number',
            'address/salutation',
            'address/street',
            'address/title',
            'address/zipcode',
            'symbol/comma',
            'symbol/dash',
            'symbol/tilde',
            'custom-snippet/custom-snippet',
        ], $content['data']);

        $originalCollection = $pluginCollection->filter(fn (Plugin $plugin) => $plugin->getName() !== 'BundleWithCustomSnippet');

        $pluginsProp = new \ReflectionProperty($pluginCollection, 'plugins');
        $pluginsProp->setAccessible(true);
        $pluginsProp->setValue($pluginCollection, $originalCollection->all());
    }

    /**
     * @param array{format: array<int, array<int, string>>, data: array<string, mixed>} $payload
     */
    #[DataProvider('renderProvider')]
    public function testRender(array $payload, string $expectedHtml): void
    {
        $url = '/api/_action/custom-snippet/render';
        $client = $this->getBrowser();
        $client->request('POST', $url, [], [], [], json_encode($payload, \JSON_THROW_ON_ERROR));

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);
        $content = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('rendered', $content);
        static::assertEquals($expectedHtml, $content['rendered']);
    }

    /**
     * @return iterable<string, array<string, string|array<string, array<mixed>>>>
     */
    public static function renderProvider(): iterable
    {
        yield 'without data and format' => [
            'payload' => [
                'format' => [],
                'data' => [],
            ],
            'expectedHtml' => '',
        ];

        yield 'without data' => [
            'payload' => [
                'format' => [],
                'data' => [
                    'address' => [
                        'name' => 'Vin',
                    ],
                ],
            ],
            'expectedHtml' => '',
        ];

        yield 'without format' => [
            'payload' => [
                'format' => [
                    [
                        'address/name',
                    ],
                ],
                'data' => [],
            ],
            'expectedHtml' => '',
        ];

        yield 'with data and format' => [
            'payload' => [
                'format' => [
                    [
                        'address/name',
                    ],
                ],
                'data' => [
                    'address' => [
                        'name' => 'Vin',
                    ],
                ],
            ],
            'expectedHtml' => 'Vin',
        ];

        yield 'render multiple lines' => [
            'payload' => [
                'format' => [
                    [
                        'address/name',
                    ],
                    [
                        'address/street',
                        'address/country',
                    ],
                ],
                'data' => [
                    'address' => [
                        'name' => 'Vin',
                        'street' => '123 Strt',
                        'country' => [
                            'translated' => [
                                'name' => 'VN',
                            ],
                        ],
                    ],
                ],
            ],
            'expectedHtml' => 'Vin<br/>123 Strt VN',
        ];

        yield 'render multiple lines with symbol' => [
            'payload' => [
                'format' => [
                    [
                        'address/name',
                        'symbol/comma',
                    ],
                    [
                        'address/street',
                        'address/country',
                    ],
                ],
                'data' => [
                    'address' => [
                        'name' => 'Vin',
                        'street' => '123 Strt',
                        'country' => [
                            'translated' => [
                                'name' => 'VN',
                            ],
                        ],
                    ],
                ],
            ],
            'expectedHtml' => 'Vin,<br/>123 Strt VN',
        ];

        yield 'render ignore empty snippet' => [
            'payload' => [
                'format' => [
                    [
                        'address/company',
                        'symbol/dash',
                        'address/department',
                        'symbol/dash',
                    ],
                    [
                        'address/name',
                    ],
                ],
                'data' => [
                    'address' => [
                        'name' => 'Vin',
                        'company' => 'cicada AG',
                        'department' => '',
                    ],
                ],
            ],
            'expectedHtml' => 'cicada AG<br/>Vin',
        ];

        yield 'render ignore empty line' => [
            'payload' => [
                'format' => [
                    [
                        'address/name',
                    ],
                    [
                        'address/street',
                        'address/country',
                    ],
                    [
                        'address/name',
                    ],
                ],
                'data' => [
                    'address' => [
                        'name' => 'Vin',
                    ],
                ],
            ],
            'expectedHtml' => 'Vin<br/>Vin',
        ];

        yield 'render line with only concat symbol' => [
            'payload' => [
                'format' => [
                    [
                        'address/name',
                        'symbol/dash',
                    ],
                ],
                'data' => [
                    'address' => [],
                ],
            ],
            'expectedHtml' => '',
        ];

        yield 'render lines with symbol comma' => [
            'payload' => [
                'format' => [
                    [
                        'address/zipcode',
                        'symbol/comma',
                        'address/city',
                    ],
                ],
                'data' => [
                    'address' => [
                        'zipcode' => '550000',
                        'city' => 'Da Nang',
                    ],
                ],
            ],
            'expectedHtml' => '550000,  Da Nang',
        ];

        yield 'render lines with empty snippet' => [
            'payload' => [
                'format' => [
                    [
                        'address/name',
                        'address/country_state',
                    ],
                ],
                'data' => [
                    'address' => [
                        'name' => 'Vin',
                        'countryState' => null,
                    ],
                ],
            ],
            'expectedHtml' => 'Vin',
        ];
    }
}

/**
 * @internal
 */
class BundleWithCustomSnippet extends Plugin
{
    public function getPath(): string
    {
        $reflected = new \ReflectionObject($this);

        return \dirname($reflected->getFileName() ?: '') . '/Fixtures/BundleWithCustomSnippet';
    }
}
