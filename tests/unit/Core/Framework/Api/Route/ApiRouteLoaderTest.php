<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\Route;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Api\ApiException;
use Cicada\Core\Framework\Api\Route\ApiRouteLoader;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityWriteGateway;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(ApiRouteLoader::class)]
class ApiRouteLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $definitionRegistry = new StaticDefinitionInstanceRegistry(
            [new ProductDefinition()],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGateway::class),
        );

        $loader = new ApiRouteLoader($definitionRegistry);

        static::assertTrue($loader->supports('resource', 'api'));

        $routes = $loader->load('resource');

        static::assertCount(8, $routes);
        static::assertArrayHasKey('api.product.detail', $routes->all());
        static::assertArrayHasKey('api.product.update', $routes->all());
        static::assertArrayHasKey('api.product.delete', $routes->all());
        static::assertArrayHasKey('api.product.list', $routes->all());
        static::assertArrayHasKey('api.product.search', $routes->all());
        static::assertArrayHasKey('api.product.search-ids', $routes->all());
        static::assertArrayHasKey('api.product.create', $routes->all());

        static::expectExceptionObject(ApiException::apiRoutesAreAlreadyLoaded());
        $loader->load('resource', 'api');
    }
}
