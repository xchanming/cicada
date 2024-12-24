<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\ApiDefinition\Generator;

use Cicada\Core\Framework\Api\ApiDefinition\DefinitionService;
use Cicada\Core\Framework\Api\ApiDefinition\Generator\BundleSchemaPathCollection;
use Cicada\Tests\Unit\Core\Framework\Api\ApiDefinition\Generator\_fixtures\CustomBundleWithApiSchema\CicadaBundleWithName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @internal
 */
#[CoversClass(BundleSchemaPathCollection::class)]
class BundleSchemaPathCollectionTest extends TestCase
{
    private Bundle $bundleWithSchemas;

    private Bundle $bundleWithoutSchemas;

    private Bundle $customBundleSchemas;

    protected function setUp(): void
    {
        $this->bundleWithSchemas = $this->createMock(Bundle::class);
        $this->bundleWithSchemas->method('getPath')->willReturn(__DIR__ . '/_fixtures/BundleWithApiSchema');
        $this->bundleWithoutSchemas = $this->createMock(Bundle::class);
        $this->bundleWithoutSchemas->method('getPath')->willReturn(__DIR__ . '/_fixtures/BundleWithoutApiSchema');
        $this->customBundleSchemas = new CicadaBundleWithName();
    }

    public function testGetPathsForStoreApi(): void
    {
        $factory = new BundleSchemaPathCollection([$this->bundleWithSchemas, $this->bundleWithoutSchemas]);

        $paths = $factory->getSchemaPaths(DefinitionService::STORE_API, null);
        static::assertContains(__DIR__ . '/_fixtures/BundleWithApiSchema/Resources/Schema/StoreApi', $paths);
        static::assertNotContains(__DIR__ . '/_fixtures/BundleWithoutApiSchema/Resources/Schema/StoreApi', $paths);
    }

    public function testGetPathsForAdminApi(): void
    {
        $factory = new BundleSchemaPathCollection([$this->bundleWithSchemas, $this->bundleWithoutSchemas]);

        $paths = $factory->getSchemaPaths(DefinitionService::API, null);
        static::assertContains(__DIR__ . '/_fixtures/BundleWithApiSchema/Resources/Schema/AdminApi', $paths);
        static::assertNotContains(__DIR__ . '/_fixtures/BundleWithoutApiSchema/Resources/Schema/AdminApi', $paths);
    }

    public function testGetPathsForSingleBundleAdminApi(): void
    {
        $factory = new BundleSchemaPathCollection([$this->bundleWithSchemas, $this->bundleWithoutSchemas, $this->customBundleSchemas]);

        $paths = $factory->getSchemaPaths(DefinitionService::API, $this->customBundleSchemas->getName());
        static::assertContains(__DIR__ . '/_fixtures/CustomBundleWithApiSchema/Resources/Schema/AdminApi', $paths);
        static::assertNotContains(__DIR__ . '/_fixtures/BundleWithApiSchema/Resources/Schema/AdminApi', $paths);
        static::assertNotContains(__DIR__ . '/_fixtures/BundleWithoutApiSchema/Resources/Schema/AdminApi', $paths);
    }

    public function testGetPathsForSingleBundleStoreApi(): void
    {
        $factory = new BundleSchemaPathCollection([$this->bundleWithSchemas, $this->bundleWithoutSchemas, $this->customBundleSchemas]);

        $paths = $factory->getSchemaPaths(DefinitionService::STORE_API, $this->customBundleSchemas->getName());
        static::assertContains(__DIR__ . '/_fixtures/CustomBundleWithApiSchema/Resources/Schema/StoreApi', $paths);
        static::assertNotContains(__DIR__ . '/_fixtures/BundleWithApiSchema/Resources/Schema/StoreApi', $paths);
        static::assertNotContains(__DIR__ . '/_fixtures/BundleWithoutApiSchema/Resources/Schema/StoreApi', $paths);
    }
}
