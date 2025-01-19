<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\Administration;

use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\Administration\Admin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Admin::class)]
class AdminTest extends TestCase
{
    public function testFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../../_fixtures/test/manifest.xml');

        $admin = $manifest->getAdmin();
        static::assertNotNull($admin);
        static::assertCount(2, $admin->getActionButtons());
        static::assertCount(2, $admin->getModules());

        $firstActionButton = $admin->getActionButtons()[0];
        static::assertEquals('viewOrder', $firstActionButton->getAction());
        static::assertEquals('order', $firstActionButton->getEntity());
        static::assertEquals('detail', $firstActionButton->getView());
        static::assertEquals('https://swag-test.com/your-order', $firstActionButton->getUrl());
        static::assertEquals([
            'zh-CN' => 'View Order',
            'en-GB' => 'Zeige Bestellung',
        ], $firstActionButton->getLabel());

        $secondActionButton = $admin->getActionButtons()[1];
        static::assertEquals('doStuffWithProducts', $secondActionButton->getAction());
        static::assertEquals('product', $secondActionButton->getEntity());
        static::assertEquals('list', $secondActionButton->getView());
        static::assertEquals('https://swag-test.com/do-stuff', $secondActionButton->getUrl());
        static::assertEquals([
            'zh-CN' => 'Do Stuff',
            'en-GB' => 'Mache Dinge',
        ], $secondActionButton->getLabel());

        $firstModule = $admin->getModules()[0];
        static::assertEquals('https://test.com', $firstModule->getSource());
        static::assertEquals('first-module', $firstModule->getName());
        static::assertEquals([
            'zh-CN' => 'My first own module',
            'en-GB' => 'Mein erstes eigenes Modul',
        ], $firstModule->getLabel());
        static::assertEquals('sw-test-structure-module', $firstModule->getParent());
        static::assertEquals(10, $firstModule->getPosition());

        $secondModule = $admin->getModules()[1];
        static::assertNull($secondModule->getSource());
        static::assertEquals('structure-module', $secondModule->getName());
        static::assertEquals([
            'zh-CN' => 'My menu entry for modules',
            'en-GB' => 'Mein Menüeintrag für Module',
        ], $secondModule->getLabel());
        static::assertEquals('sw-catalogue', $secondModule->getParent());
        static::assertEquals(50, $secondModule->getPosition());

        $mainModule = $admin->getMainModule();

        static::assertNotNull($mainModule);
        static::assertEquals('https://main-module', $mainModule->getSource());
    }

    public function testModulesWithStructureElements(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/manifestWithStructureElement.xml');

        $admin = $manifest->getAdmin();
        static::assertNotNull($admin);

        $moduleWithStructureElement = $admin->getModules()[0];

        static::assertNull($moduleWithStructureElement->getSource());
        static::assertEquals('sw-catalogue', $moduleWithStructureElement->getParent());
        static::assertEquals(50, $moduleWithStructureElement->getPosition());
    }

    public function testMainModuleIsOptional(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/manifestWithoutMainModule.xml');

        $admin = $manifest->getAdmin();
        static::assertNotNull($admin);

        static::assertNull($admin->getMainModule());
    }

    public function testManifestWithMultipleMainModulesIsInvalid(): void
    {
        $this->expectException(AppException::class);

        Manifest::createFromXmlFile(__DIR__ . '/_fixtures/manifestWithTwoMainModules.xml');
    }
}
