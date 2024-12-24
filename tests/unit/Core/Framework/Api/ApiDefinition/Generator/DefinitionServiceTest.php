<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\ApiDefinition\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\ApiDefinition\DefinitionService;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInstanceRegistry;

/**
 * @internal
 */
#[CoversClass(DefinitionService::class)]
class DefinitionServiceTest extends TestCase
{
    public function testConversionFromStringToApiType(): void
    {
        $definitionService = new DefinitionService(
            $this->createMock(DefinitionInstanceRegistry::class),
            $this->createMock(SalesChannelDefinitionInstanceRegistry::class)
        );

        static::assertNull($definitionService->toApiType('foobar'));
        static::assertSame(DefinitionService::TYPE_JSON_API, $definitionService->toApiType('jsonapi'));
        static::assertSame(DefinitionService::TYPE_JSON, $definitionService->toApiType('json'));
    }
}
