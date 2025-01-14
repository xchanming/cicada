<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\Indexing;

use Cicada\Elasticsearch\Framework\Indexing\CreateAliasTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * @internal
 */
#[CoversClass(CreateAliasTask::class)]
class CreateAliasTaskTest extends TestCase
{
    public function testShouldRun(): void
    {
        static::assertTrue(CreateAliasTask::shouldRun(new ParameterBag(['elasticsearch.enabled' => true])));
        static::assertFalse(CreateAliasTask::shouldRun(new ParameterBag(['elasticsearch.enabled' => false])));
    }

    public function testGetDefaultInterval(): void
    {
        static::assertSame(300, CreateAliasTask::getDefaultInterval());
    }

    public function testGetTaskName(): void
    {
        static::assertSame('cicada.elasticsearch.create.alias', CreateAliasTask::getTaskName());
    }
}
