<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Rule\DataAbstractionLayer;

use Cicada\Core\Content\Rule\DataAbstractionLayer\RulePayloadSubscriber;
use Cicada\Core\Content\Rule\DataAbstractionLayer\RulePayloadUpdater;
use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\Framework\Rule\Container\OrRule;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
class RulePayloadSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Connection $connection;

    private RulePayloadSubscriber $rulePayloadSubscriber;

    private Context $context;

    private MockObject&RulePayloadUpdater $updater;

    private RuleDefinition $ruleDefinition;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->connection = static::getContainer()->get(Connection::class);
        $this->updater = $this->createMock(RulePayloadUpdater::class);

        $this->rulePayloadSubscriber = new RulePayloadSubscriber(
            $this->updater,
            static::getContainer()->get(ScriptTraces::class),
            static::getContainer()->getParameter('kernel.cache_dir'),
            static::getContainer()->getParameter('kernel.debug')
        );

        $this->ruleDefinition = static::getContainer()->get(RuleDefinition::class);
    }

    public function testLoadValidRuleWithoutPayload(): void
    {
        $id = Uuid::randomHex();
        $rule = (new RuleEntity())->assign(['id' => $id, 'payload' => null, 'invalid' => false, '_uniqueIdentifier' => $id]);
        $loadedEvent = new EntityLoadedEvent($this->ruleDefinition, [$rule], $this->context);

        static::assertNull($rule->getPayload());

        $this->updater
            ->expects(static::once())
            ->method('update')
            ->with([$id])
            ->willReturn([$id => ['payload' => serialize(new AndRule()), 'invalid' => false]]);

        $this->rulePayloadSubscriber->unserialize($loadedEvent);

        static::assertNotNull($rule->getPayload());
        static::assertFalse($rule->isInvalid());
    }

    public function testLoadInvalidRuleWithoutPayload(): void
    {
        $id = Uuid::randomHex();
        $rule = (new RuleEntity())->assign(['id' => $id, 'payload' => null, 'invalid' => true, '_uniqueIdentifier' => $id]);
        $loadedEvent = new EntityLoadedEvent($this->ruleDefinition, [$rule], $this->context);

        static::assertNull($rule->getPayload());
        static::assertTrue($rule->isInvalid());

        $this->updater
            ->expects(static::never())
            ->method('update');

        $this->rulePayloadSubscriber->unserialize($loadedEvent);

        static::assertNull($rule->getPayload());
        static::assertTrue($rule->isInvalid());
    }

    public function testLoadValidRuleWithPayload(): void
    {
        $id = Uuid::randomHex();
        $rule = (new RuleEntity())->assign(['id' => $id, 'payload' => serialize(new AndRule()), 'invalid' => false, '_uniqueIdentifier' => $id]);
        $loadedEvent = new EntityLoadedEvent($this->ruleDefinition, [$rule], $this->context);

        static::assertNotNull($rule->getPayload());

        $this->updater
            ->expects(static::never())
            ->method('update');
        $this->rulePayloadSubscriber->unserialize($loadedEvent);

        static::assertNotNull($rule->getPayload());
        static::assertFalse($rule->isInvalid());
    }

    public function testLoadValidRulesWithoutPayload(): void
    {
        $id = Uuid::randomHex();
        $id2 = Uuid::randomHex();
        $rule = (new RuleEntity())->assign(['id' => $id, 'payload' => null, 'invalid' => false, '_uniqueIdentifier' => $id]);
        $rule2 = (new RuleEntity())->assign(['id' => $id2, 'payload' => null, 'invalid' => false, '_uniqueIdentifier' => $id2]);
        $loadedEvent = new EntityLoadedEvent($this->ruleDefinition, [$rule, $rule2], $this->context);

        static::assertNull($rule->getPayload());

        $this->updater
            ->expects(static::once())
            ->method('update')
            ->with([$id, $id2])
                ->willReturn(
                    [
                        $id => ['payload' => serialize(new AndRule()), 'invalid' => false],
                        $id2 => ['payload' => serialize(new OrRule()), 'invalid' => false],
                    ]
                );
        $this->rulePayloadSubscriber->unserialize($loadedEvent);

        static::assertNotNull($rule->getPayload());
        static::assertFalse($rule->isInvalid());
        static::assertNotNull($rule2->getPayload());
        static::assertInstanceOf(OrRule::class, $rule2->getPayload());
        static::assertFalse($rule2->isInvalid());
    }

    public function testLoadValidAndInvalidRulesWithoutPayload(): void
    {
        $id = Uuid::randomHex();
        $id2 = Uuid::randomHex();
        $rule = (new RuleEntity())->assign(['id' => $id, 'payload' => null, 'invalid' => false, '_uniqueIdentifier' => $id]);
        $rule2 = (new RuleEntity())->assign(['id' => $id2, 'payload' => null, 'invalid' => true, '_uniqueIdentifier' => $id2]);
        $loadedEvent = new EntityLoadedEvent($this->ruleDefinition, [$rule, $rule2], $this->context);

        static::assertNull($rule->getPayload());

        $this->updater
            ->expects(static::once())
            ->method('update')
            ->with([$id])
            ->willReturn(
                [$id => ['payload' => serialize(new AndRule()), 'invalid' => false]]
            );

        $this->rulePayloadSubscriber->unserialize($loadedEvent);

        static::assertNotNull($rule->getPayload());
        static::assertFalse($rule->isInvalid());
        static::assertNull($rule2->getPayload());
        static::assertTrue($rule2->isInvalid());
    }

    public function testLoadValidRulesFromDatabase(): void
    {
        $id = Uuid::randomHex();
        $this->connection->createQueryBuilder()
            ->insert('rule')
            ->values(['id' => ':id', 'name' => ':name', 'priority' => 3, 'invalid' => '0', 'created_at' => ':createdAt'])
            ->setParameter('name', 'Rule')
            ->setParameter('id', Uuid::fromHexToBytes($id))
            ->setParameter('createdAt', (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT))
            ->executeStatement();

        $this->connection->createQueryBuilder()
            ->insert('rule_condition')
            ->values(['id' => ':id', 'type' => ':type', 'value' => 'null', 'position' => '0', 'rule_id' => ':ruleId', 'created_at' => ':createdAt'])
            ->setParameter('id', Uuid::randomBytes())
            ->setParameter('type', (new AndRule())->getName())
            ->setParameter('ruleId', Uuid::fromHexToBytes($id))
            ->setParameter('createdAt', (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT))
            ->executeStatement();

        $rule = static::getContainer()->get('rule.repository')->search(new Criteria([$id]), $this->context)->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertNotNull($rule->getPayload());
        static::assertInstanceOf(AndRule::class, $rule->getPayload());
        static::assertFalse($rule->isInvalid());

        $ruleData = $this->connection->createQueryBuilder()
            ->select('payload', 'invalid')
            ->from('rule')
            ->where('id = :id')
            ->setParameter('id', Uuid::fromHexToBytes($id))
            ->executeQuery()
            ->fetchAssociative();

        static::assertIsArray($ruleData);
        static::assertNotNull($ruleData['payload']);
        static::assertSame(0, (int) $ruleData['invalid']);
    }

    public function testLoadInvalidRulesFromDatabase(): void
    {
        $id = Uuid::randomHex();
        $this->connection->createQueryBuilder()
            ->insert('rule')
            ->values(['id' => ':id', 'name' => ':name', 'priority' => 3, 'invalid' => '0', 'created_at' => ':createdAt'])
            ->setParameter('name', 'Rule')
            ->setParameter('id', Uuid::fromHexToBytes($id))
            ->setParameter('createdAt', (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT))
            ->executeStatement();

        $this->connection->createQueryBuilder()
            ->insert('rule_condition')
            ->values(['id' => ':id', 'type' => ':type', 'value' => 'null', 'position' => '0', 'rule_id' => ':ruleId', 'created_at' => ':createdAt'])
            ->setParameter('id', Uuid::randomBytes())
            ->setParameter('type', 'invalid')
            ->setParameter('ruleId', Uuid::fromHexToBytes($id))
            ->setParameter('createdAt', (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT))
            ->executeStatement();

        $rule = static::getContainer()->get('rule.repository')->search(new Criteria([$id]), $this->context)->get($id);
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertNull($rule->getPayload());
        static::assertTrue($rule->isInvalid());

        $ruleData = $this->connection->createQueryBuilder()
            ->select('payload', 'invalid')
            ->from('rule')
            ->where('id = :id')
            ->setParameter('id', Uuid::fromHexToBytes($id))
            ->executeQuery()
            ->fetchAssociative();

        static::assertIsArray($ruleData);
        static::assertNull($ruleData['payload']);
        static::assertSame(1, (int) $ruleData['invalid']);
    }
}
