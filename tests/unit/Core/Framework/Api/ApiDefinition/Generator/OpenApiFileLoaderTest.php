<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\ApiDefinition\Generator;

use Cicada\Core\Framework\Api\ApiDefinition\Generator\OpenApiFileLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OpenApiFileLoader::class)]
class OpenApiFileLoaderTest extends TestCase
{
    public function testMergingOfFiles(): void
    {
        $paths = [__DIR__ . '/_fixtures/Api/ApiDefinition/Generator/Schema/StoreApi'];
        $fsLoader = new OpenApiFileLoader($paths);

        $spec = $fsLoader->loadOpenapiSpecification();

        static::assertArrayHasKey('paths', $spec);
        static::assertArrayHasKey('components', $spec);
        static::assertArrayHasKey('/_action/order_delivery/{orderDeliveryId}/state/{transition}', $spec['paths']);
        static::assertArrayHasKey('schemas', $spec['components']);
        static::assertCount(2, $spec['components']['schemas']);
    }

    public function testEmptyFileLoader(): void
    {
        $fsLoader = new OpenApiFileLoader([]);

        $spec = $fsLoader->loadOpenapiSpecification();

        static::assertSame(
            [
                'paths' => [],
                'components' => [],
                'tags' => [],
            ],
            $spec
        );
    }

    public function testSchemaOverrides(): void
    {
        $paths = [
            __DIR__ . '/_fixtures/Api/ApiDefinition/Generator/Schema/StoreApi',
            __DIR__ . '/_fixtures/BundleWithOverride/Resources/Schema/StoreApi',
        ];
        $fsLoader = new OpenApiFileLoader($paths);

        $spec = $fsLoader->loadOpenapiSpecification();

        static::assertSame('Override', $spec['paths']['/_action/order_delivery/{orderDeliveryId}/state/{transition}']['post']['description']);
    }
}
