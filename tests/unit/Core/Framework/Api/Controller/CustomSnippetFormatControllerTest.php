<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\Controller;

use Cicada\Core\Framework\Api\Controller\CustomSnippetFormatController;
use Cicada\Core\Framework\Plugin\KernelPluginCollection;
use Cicada\Tests\Unit\Core\Framework\Api\Controller\Fixtures\BundleWithCustomSnippet\BundleWithCustomSnippet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(CustomSnippetFormatController::class)]
class CustomSnippetFormatControllerTest extends TestCase
{
    /**
     * @var KernelPluginCollection&MockObject
     */
    private KernelPluginCollection $pluginCollection;

    /**
     * @var Environment&MockObject
     */
    private Environment $twig;

    private CustomSnippetFormatController $controller;

    protected function setUp(): void
    {
        $this->pluginCollection = $this->createMock(KernelPluginCollection::class);
        $this->twig = $this->createMock(Environment::class);
        $this->controller = new CustomSnippetFormatController($this->pluginCollection, $this->twig);
    }

    public function testGetSnippetsWithoutPlugins(): void
    {
        $response = $this->controller->snippets();
        $content = $response->getContent();
        static::assertNotFalse($content);
        $content = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

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
        $this->pluginCollection->expects(static::once())->method('getActives')->willReturn([$plugin]);

        $response = $this->controller->snippets();
        $content = $response->getContent();
        static::assertNotFalse($content);
        $content = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

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
    }

    public function testRender(): void
    {
        $request = new Request();
        $request->request->set('data', [
            'customer' => [
                'name' => 'Vin',
            ],
        ]);
        $request->request->set('format', [
            [
                'address/name',
            ],
        ]);
        $this->twig->expects(static::once())->method('render')->with('@Framework/snippets/render.html.twig', [
            'customer' => [
                'name' => 'Vin',
            ],
            'format' => [
                [
                    'address/name',
                ],
            ],
        ])->willReturn('Rendered html');

        $response = $this->controller->render($request);
        $content = $response->getContent();
        static::assertNotFalse($content);
        $content = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('rendered', $content);
        static::assertEquals('Rendered html', $content['rendered']);
    }
}
