<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Write;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteResultFactory;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\CountryDefinition;
use Cicada\Core\System\Tax\TaxDefinition;
use Cicada\Core\Test\Stub\DataAbstractionLayer\EmptyEntityExistence;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(EntityWriteResultFactory::class)]
class EntityWriteResultFactoryTest extends TestCase
{
    /**
     * @param array<array<string, mixed>> $commands
     * @param array<string, array<string, array<string, mixed>>> $expected
     */
    #[DataProvider('buildResultProvider')]
    public function testBuildResult(array $commands, array $expected): void
    {
        $registry = new StaticDefinitionInstanceRegistry(
            [CountryDefinition::class, TaxDefinition::class],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        $factory = new EntityWriteResultFactory(
            $registry,
            $this->createMock(Connection::class)
        );

        $queue = new WriteCommandQueue();

        // add all commands to queue, use the identifier system of DAL
        foreach ($commands as $command) {
            // fake class to reduce constructor complexity
            $command = new UpdateCommandStub(
                $command['payload'],
                $command['primaryKey'],
                $registry->get($command['definition'])
            );

            $identifier = WriteCommandQueue::hashedPrimary($registry, $command);
            $queue->add($command->getEntityName(), $identifier, $command);
        }

        $result = $factory->build($queue);

        // loop over expected written entity names
        foreach ($expected as $entity => $records) {
            static::assertArrayHasKey($entity, $result, 'Expected write results for entity ' . $entity);

            static::assertCount(\count($records), $result[$entity], 'Expected write results for entity ' . $entity);

            // now loop over the written records and compare the payloads
            foreach ($result[$entity] as $written) {
                $id = $written->getPrimaryKey();

                static::assertIsString($id, 'Expected write result to have a primary key as string in this test');

                static::assertArrayHasKey($id, $records, \sprintf('Primary key %s was not expected to be written', $id));

                static::assertEquals($records[$id], $written->getPayload(), 'Expected payload to be equal');
            }
        }
    }

    public static function buildResultProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'Test single definition, single command' => [
            'commands' => [
                [
                    'payload' => ['id' => $ids->getBytes('country-1'), 'active' => false],
                    'primaryKey' => ['id' => $ids->getBytes('country-1')],
                    'definition' => CountryDefinition::class,
                ],
            ],
            'expected' => [
                'country' => [
                    $ids->get('country-1') => ['id' => $ids->get('country-1'), 'active' => false],
                ],
            ],
        ];

        yield 'Test single definition, multiple commands' => [
            'commands' => [
                [
                    'payload' => ['id' => $ids->getBytes('country-1'), 'active' => false],
                    'primaryKey' => ['id' => $ids->getBytes('country-1')],
                    'definition' => CountryDefinition::class,
                ],
                [
                    'payload' => ['id' => $ids->getBytes('country-2'), 'active' => true],
                    'primaryKey' => ['id' => $ids->getBytes('country-2')],
                    'definition' => CountryDefinition::class,
                ],
            ],
            'expected' => [
                'country' => [
                    $ids->get('country-1') => ['id' => $ids->get('country-1'), 'active' => false],
                    $ids->get('country-2') => ['id' => $ids->get('country-2'), 'active' => true],
                ],
            ],
        ];

        yield 'Test multiple definitions, multiple commands' => [
            'commands' => [
                [
                    'payload' => ['id' => $ids->getBytes('country-1'), 'active' => false],
                    'primaryKey' => ['id' => $ids->getBytes('country-1')],
                    'definition' => CountryDefinition::class,
                ],
                [
                    'payload' => ['id' => $ids->getBytes('country-2'), 'active' => true],
                    'primaryKey' => ['id' => $ids->getBytes('country-2')],
                    'definition' => CountryDefinition::class,
                ],
                [
                    'payload' => ['id' => $ids->getBytes('tax-1'), 'tax_rate' => 10],
                    'primaryKey' => ['id' => $ids->getBytes('tax-1')],
                    'definition' => TaxDefinition::class,
                ],
                [
                    'payload' => ['id' => $ids->getBytes('tax-2'), 'tax_rate' => 11],
                    'primaryKey' => ['id' => $ids->getBytes('tax-2')],
                    'definition' => TaxDefinition::class,
                ],
            ],
            'expected' => [
                'country' => [
                    $ids->get('country-1') => ['id' => $ids->get('country-1'), 'active' => false],
                    $ids->get('country-2') => ['id' => $ids->get('country-2'), 'active' => true],
                ],
                'tax' => [
                    $ids->get('tax-1') => ['id' => $ids->get('tax-1'), 'taxRate' => 10],
                    $ids->get('tax-2') => ['id' => $ids->get('tax-2'), 'taxRate' => 11],
                ],
            ],
        ];

        yield 'Test merge payload for same definition and same command primary key' => [
            'commands' => [
                [
                    'payload' => ['id' => $ids->getBytes('country-1'), 'active' => false],
                    'primaryKey' => ['id' => $ids->getBytes('country-1')],
                    'definition' => CountryDefinition::class,
                ],
                [
                    'payload' => ['id' => $ids->getBytes('country-1'), 'position' => 10],
                    'primaryKey' => ['id' => $ids->getBytes('country-1')],
                    'definition' => CountryDefinition::class,
                ],
            ],
            'expected' => [
                'country' => [
                    $ids->get('country-1') => [
                        'id' => $ids->get('country-1'),
                        'active' => false,
                        'position' => 10,
                    ],
                ],
            ],
        ];
    }
}

/**
 * @internal
 */
class UpdateCommandStub extends UpdateCommand
{
    public function __construct(array $payload, array $primaryKey, ?EntityDefinition $definition = null)
    {
        $definition = $definition ?? new CountryDefinition();

        parent::__construct(
            definition: $definition,
            payload: $payload,
            primaryKey: $primaryKey,
            existence: new EmptyEntityExistence(),
            path: '/' . Uuid::randomHex()
        );
    }
}
