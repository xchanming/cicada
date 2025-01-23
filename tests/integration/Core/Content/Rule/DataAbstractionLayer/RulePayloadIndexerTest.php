<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Rule\DataAbstractionLayer;

use Cicada\Core\Content\Rule\DataAbstractionLayer\RuleIndexer;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationCollection;
use Cicada\Core\Framework\Migration\MigrationRuntime;
use Cicada\Core\Framework\Migration\MigrationSource;
use Cicada\Core\Framework\Plugin;
use Cicada\Core\Framework\Plugin\Context\ActivateContext;
use Cicada\Core\Framework\Plugin\Context\DeactivateContext;
use Cicada\Core\Framework\Plugin\Context\InstallContext;
use Cicada\Core\Framework\Plugin\Context\UninstallContext;
use Cicada\Core\Framework\Plugin\Context\UpdateContext;
use Cicada\Core\Framework\Plugin\Event\PluginLifecycleEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostDeactivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostInstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostUninstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostUpdateEvent;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\Framework\Rule\Container\OrRule;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\SalesChannelRule;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Migration\Test\NullConnection;
use Cicada\Core\System\Currency\Rule\CurrencyRule;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class RulePayloadIndexerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Context $context;

    /**
     * @var EntityRepository<RuleCollection>
     */
    private EntityRepository $ruleRepository;

    private RuleIndexer $indexer;

    private Connection $connection;

    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->ruleRepository = static::getContainer()->get('rule.repository');
        $this->indexer = static::getContainer()->get(RuleIndexer::class);
        $this->connection = static::getContainer()->get(Connection::class);
        $this->context = Context::createDefaultContext();
        $this->eventDispatcher = static::getContainer()->get('event_dispatcher');
    }

    public function testIndex(): void
    {
        $id = Uuid::randomHex();
        $currencyId1 = Uuid::randomHex();
        $currencyId2 = Uuid::randomHex();

        $data = [
            'id' => $id,
            'name' => 'test rule',
            'priority' => 1,
            'conditions' => [
                [
                    'type' => (new OrRule())->getName(),
                    'children' => [
                        [
                            'type' => (new CurrencyRule())->getName(),
                            'value' => [
                                'currencyIds' => [
                                    $currencyId1,
                                    $currencyId2,
                                ],
                                'operator' => CurrencyRule::OPERATOR_EQ,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->ruleRepository->create([$data], $this->context);

        $this->connection->update('rule', ['payload' => null, 'invalid' => '1'], ['HEX(1)' => '1']);
        $rule = $this->ruleRepository->search(new Criteria([$id]), $this->context)->getEntities()->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertNull($rule->get('payload'));

        $this->indexer->handle(new EntityIndexingMessage([$id]));

        $rule = $this->ruleRepository->search(new Criteria([$id]), $this->context)->getEntities()->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertInstanceOf(Rule::class, $rule->getPayload());
        static::assertEquals(
            new AndRule([new OrRule([(new CurrencyRule())->assign(['currencyIds' => [$currencyId1, $currencyId2]])])]),
            $rule->getPayload()
        );
    }

    public function testRefresh(): void
    {
        $id = Uuid::randomHex();
        $currencyId1 = Uuid::randomHex();
        $currencyId2 = Uuid::randomHex();

        $data = [
            'id' => $id,
            'name' => 'test rule',
            'priority' => 1,
            'conditions' => [
                [
                    'type' => (new OrRule())->getName(),
                    'children' => [
                        [
                            'type' => (new CurrencyRule())->getName(),
                            'value' => [
                                'currencyIds' => [
                                    $currencyId1,
                                    $currencyId2,
                                ],
                                'operator' => CurrencyRule::OPERATOR_EQ,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->ruleRepository->create([$data], $this->context);

        $rule = $this->ruleRepository->search(new Criteria([$id]), $this->context)->getEntities()->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertInstanceOf(Rule::class, $rule->getPayload());
        static::assertEquals(
            new AndRule([new OrRule([(new CurrencyRule())->assign(['currencyIds' => [$currencyId1, $currencyId2]])])]),
            $rule->getPayload()
        );
    }

    public function testRefreshWithMultipleRules(): void
    {
        $id = Uuid::randomHex();
        $rule2Id = Uuid::randomHex();
        $currencyId1 = Uuid::randomHex();
        $currencyId2 = Uuid::randomHex();
        $salesChannelId1 = Uuid::randomHex();
        $salesChannelId2 = Uuid::randomHex();

        $data = [
            [
                'id' => $id,
                'name' => 'test rule',
                'priority' => 1,
                'conditions' => [
                    [
                        'type' => (new OrRule())->getName(),
                        'children' => [
                            [
                                'type' => (new CurrencyRule())->getName(),
                                'value' => [
                                    'currencyIds' => [
                                        $currencyId1,
                                        $currencyId2,
                                    ],
                                    'operator' => CurrencyRule::OPERATOR_EQ,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => $rule2Id,
                'name' => 'second rule',
                'priority' => 42,
                'conditions' => [
                    [
                        'type' => (new SalesChannelRule())->getName(),
                        'value' => [
                            'salesChannelIds' => [
                                $salesChannelId1,
                                $salesChannelId2,
                            ],
                            'operator' => CurrencyRule::OPERATOR_EQ,
                        ],
                    ],
                ],
            ],
        ];

        $this->ruleRepository->create($data, $this->context);

        $this->connection->update('rule', ['payload' => null, 'invalid' => '1'], ['HEX(1)' => '1']);
        $rule = $this->ruleRepository->search(new Criteria([$id]), $this->context)->getEntities()->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertNull($rule->get('payload'));

        $this->indexer->handle(new EntityIndexingMessage([$id, $rule2Id]));

        $rules = $this->ruleRepository->search(new Criteria([$id, $rule2Id]), $this->context);
        $rule = $rules->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertInstanceOf(Rule::class, $rule->getPayload());
        static::assertEquals(
            new AndRule([new OrRule([(new CurrencyRule())->assign(['currencyIds' => [$currencyId1, $currencyId2]])])]),
            $rule->getPayload()
        );
        $rule = $rules->get($rule2Id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertInstanceOf(Rule::class, $rule->getPayload());
        static::assertEquals(
            new AndRule([(new SalesChannelRule())->assign(['salesChannelIds' => [$salesChannelId1, $salesChannelId2]])]),
            $rule->getPayload()
        );
    }

    public function testIndexWithMultipleRules(): void
    {
        $id = Uuid::randomHex();
        $rule2Id = Uuid::randomHex();
        $currencyId1 = Uuid::randomHex();
        $currencyId2 = Uuid::randomHex();
        $salesChannelId1 = Uuid::randomHex();
        $salesChannelId2 = Uuid::randomHex();

        $data = [
            [
                'id' => $id,
                'name' => 'test rule',
                'priority' => 1,
                'conditions' => [
                    [
                        'type' => (new OrRule())->getName(),
                        'children' => [
                            [
                                'type' => (new CurrencyRule())->getName(),
                                'value' => [
                                    'currencyIds' => [
                                        $currencyId1,
                                        $currencyId2,
                                    ],
                                    'operator' => CurrencyRule::OPERATOR_EQ,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => $rule2Id,
                'name' => 'second rule',
                'priority' => 42,
                'conditions' => [
                    [
                        'type' => (new SalesChannelRule())->getName(),
                        'value' => [
                            'salesChannelIds' => [
                                $salesChannelId1,
                                $salesChannelId2,
                            ],
                            'operator' => SalesChannelRule::OPERATOR_EQ,
                        ],
                    ],
                ],
            ],
        ];

        $this->ruleRepository->create($data, $this->context);

        $rules = $this->ruleRepository->search(new Criteria([$id, $rule2Id]), $this->context);
        $rule = $rules->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertInstanceOf(Rule::class, $rule->getPayload());
        static::assertEquals(
            new AndRule([new OrRule([(new CurrencyRule())->assign(['currencyIds' => [$currencyId1, $currencyId2]])])]),
            $rule->getPayload()
        );
        $rule = $rules->get($rule2Id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertInstanceOf(Rule::class, $rule->getPayload());
        static::assertEquals(
            new AndRule([(new SalesChannelRule())->assign(['salesChannelIds' => [$salesChannelId1, $salesChannelId2]])]),
            $rule->getPayload()
        );
    }

    public function testIndexWithMultipleRootConditions(): void
    {
        $id = Uuid::randomHex();

        $data = [
            'id' => $id,
            'name' => 'test rule',
            'priority' => 1,
            'conditions' => [
                [
                    'type' => (new OrRule())->getName(),
                    'children' => [
                        [
                            'type' => (new AndRule())->getName(),
                            'children' => [
                                [
                                    'type' => (new CurrencyRule())->getName(),
                                    'value' => [
                                        'currencyIds' => [
                                            Uuid::randomHex(),
                                            Uuid::randomHex(),
                                        ],
                                        'operator' => CurrencyRule::OPERATOR_EQ,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => (new OrRule())->getName(),
                ],
            ],
        ];

        $this->ruleRepository->create([$data], $this->context);

        $this->connection->update('rule', ['payload' => null, 'invalid' => '1'], ['HEX(1)' => '1']);
        $rule = $this->ruleRepository->search(new Criteria([$id]), $this->context)->getEntities()->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertNull($rule->get('payload'));
        $this->indexer->handle(new EntityIndexingMessage([$id]));

        $rule = $this->ruleRepository->search(new Criteria([$id]), $this->context)->getEntities()->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertInstanceOf(AndRule::class, $rule->getPayload());

        static::assertCount(2, $rule->getPayload()->getRules());
        static::assertContainsOnlyInstancesOf(OrRule::class, $rule->getPayload()->getRules());
    }

    public function testIndexWithRootRuleNotAndRule(): void
    {
        $id = Uuid::randomHex();
        $currencyId1 = Uuid::randomHex();
        $currencyId2 = Uuid::randomHex();

        $data = [
            'id' => $id,
            'name' => 'test rule',
            'priority' => 1,
            'conditions' => [
                [
                    'type' => (new CurrencyRule())->getName(),
                    'value' => [
                        'currencyIds' => [
                            $currencyId1,
                            $currencyId2,
                        ],
                        'operator' => CurrencyRule::OPERATOR_EQ,
                    ],
                ],
            ],
        ];

        $this->ruleRepository->create([$data], $this->context);

        $this->connection->update('rule', ['payload' => null, 'invalid' => '1'], ['HEX(1)' => '1']);
        $rule = $this->ruleRepository->search(new Criteria([$id]), $this->context)->getEntities()->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertNull($rule->get('payload'));

        $this->indexer->handle(new EntityIndexingMessage([$id]));

        $rule = $this->ruleRepository->search(new Criteria([$id]), $this->context)->getEntities()->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertInstanceOf(Rule::class, $rule->getPayload());
        static::assertEquals(
            new AndRule([(new CurrencyRule())->assign(['currencyIds' => [$currencyId1, $currencyId2]])]),
            $rule->getPayload()
        );
    }

    public function testRefreshWithRootRuleNotAndRule(): void
    {
        $id = Uuid::randomHex();
        $currencyId1 = Uuid::randomHex();
        $currencyId2 = Uuid::randomHex();

        $data = [
            'id' => $id,
            'name' => 'test rule',
            'priority' => 1,
            'conditions' => [
                [
                    'type' => (new CurrencyRule())->getName(),
                    'value' => [
                        'currencyIds' => [
                            $currencyId1,
                            $currencyId2,
                        ],
                        'operator' => CurrencyRule::OPERATOR_EQ,
                    ],
                ],
            ],
        ];

        $this->ruleRepository->create([$data], $this->context);

        $rule = $this->ruleRepository->search(new Criteria([$id]), $this->context)->getEntities()->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertInstanceOf(Rule::class, $rule->getPayload());
        static::assertEquals(
            new AndRule([(new CurrencyRule())->assign(['currencyIds' => [$currencyId1, $currencyId2]])]),
            $rule->getPayload()
        );
    }

    #[DataProvider('dataProviderForTestPostEventNullsPayload')]
    public function testPostEventNullsPayload(PluginLifecycleEvent $event): void
    {
        $payload = serialize(new AndRule());

        for ($i = 0; $i < 21; ++$i) {
            $this->connection->createQueryBuilder()
                ->insert('rule')
                ->values(['id' => ':id', 'name' => ':name', 'priority' => 1, 'payload' => ':payload', 'created_at' => ':createdAt'])
                ->setParameter('id', Uuid::randomBytes())
                ->setParameter('payload', $payload)
                ->setParameter('name', 'Rule' . $i)
                ->setParameter('createdAt', (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT))
                ->executeStatement();
        }

        $this->eventDispatcher->dispatch($event);

        $rules = $this->connection->createQueryBuilder()
            ->select('id', 'payload', 'invalid')
            ->from('rule')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rules as $rule) {
            static::assertEquals(0, $rule['invalid']);
            static::assertNull($rule['payload']);
            static::assertNotNull($rule['id']);
        }
    }

    /**
     * @return list<array<PluginLifecycleEvent>>
     */
    public static function dataProviderForTestPostEventNullsPayload(): array
    {
        $plugin = new PluginEntity();
        $plugin->setName('TestPlugin');
        $plugin->setBaseClass(RulePlugin::class);
        $plugin->setPath('');

        $context = Context::createDefaultContext();
        $rulePlugin = new RulePlugin(false, '');

        $collection = new MigrationCollection(
            new MigrationSource('asd', []),
            new MigrationRuntime(new NullConnection(), new NullLogger()),
            new NullConnection()
        );

        return [
            [new PluginPostInstallEvent($plugin, new InstallContext($rulePlugin, $context, '', '', $collection))],
            [new PluginPostActivateEvent($plugin, new ActivateContext($rulePlugin, $context, '', '', $collection))],
            [new PluginPostUpdateEvent($plugin, new UpdateContext($rulePlugin, $context, '', '', $collection, ''))],
            [new PluginPostDeactivateEvent($plugin, new DeactivateContext($rulePlugin, $context, '', '', $collection))],
            [new PluginPostUninstallEvent($plugin, new UninstallContext($rulePlugin, $context, '', '', $collection, true))],
        ];
    }
}

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class RulePlugin extends Plugin
{
}
