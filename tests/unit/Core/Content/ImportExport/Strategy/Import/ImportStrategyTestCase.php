<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ImportExport\Strategy\Import;

use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversNothing]
abstract class ImportStrategyTestCase extends TestCase
{
    protected EventDispatcherInterface&MockObject $eventDispatcher;

    protected EntityRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
    }

    public static function importProvider(): \Generator
    {
        yield 'createEntities' => [
            'config' => new Config(
                mapping: [],
                parameters: [
                    'createEntities' => true,
                    'updateEntities' => false,
                ],
                updateBy: []
            ),
            'method' => 'create',
        ];

        yield 'updateEntities' => [
            'config' => new Config(
                mapping: [],
                parameters: [
                    'createEntities' => false,
                    'updateEntities' => true,
                ],
                updateBy: []
            ),
            'method' => 'update',
        ];

        yield 'upsertEntities' => [
            'config' => new Config(
                mapping: [],
                parameters: [
                    'createEntities' => true,
                    'updateEntities' => true,
                ],
                updateBy: []
            ),
            'method' => 'upsert',
        ];
    }
}
