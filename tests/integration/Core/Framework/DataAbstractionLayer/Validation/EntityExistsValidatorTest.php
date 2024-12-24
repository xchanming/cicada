<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Validation;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\DataAbstractionLayer\VersionManager;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Locale\LocaleDefinition;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class EntityExistsValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testCriteriaObjectIsNotModified(): void
    {
        $validator = $this->getValidator();

        $criteria = new Criteria();
        $criteria->setLimit(50);

        $context = Context::createDefaultContext();

        $constraint = new EntityExists(
            ['context' => $context, 'entity' => LocaleDefinition::ENTITY_NAME, 'criteria' => $criteria]
        );

        $validator->validate(Uuid::randomHex(), [$constraint]);

        static::assertCount(0, $criteria->getFilters());
        static::assertSame(50, $criteria->getLimit());
    }

    public function testValidatorWorks(): void
    {
        $repository = $this->createRepository();

        $context = Context::createDefaultContext();
        $id1 = Uuid::randomHex();
        $id2 = Uuid::randomHex();

        $repository->create(
            [
                ['id' => $id1, 'name' => 'Test 1', 'territory' => 'test', 'code' => 'test' . $id1],
                ['id' => $id2, 'name' => 'Test 2', 'territory' => 'test', 'code' => 'test' . $id2],
            ],
            $context
        );

        $validator = $this->getValidator();
        $constraint = new EntityExists(
            ['context' => $context, 'entity' => LocaleDefinition::ENTITY_NAME]
        );

        $violations = $validator->validate($id1, $constraint);
        // Entity exists and therefore there are no violations.
        static::assertCount(0, $violations);

        $violations = $validator->validate($id2, $constraint);
        // Entity exists and therefore there are no violations.
        static::assertCount(0, $violations);

        $violations = $validator->validate(Uuid::randomHex(), $constraint);
        // Entity does not exist and therefore there is one violation.
        static::assertCount(1, $violations);
    }

    public function testValidatorWorksWithCompositeConstraint(): void
    {
        $repository = $this->createRepository();

        $context = Context::createDefaultContext();
        $id1 = Uuid::randomHex();
        $id2 = Uuid::randomHex();

        $repository->create(
            [
                ['id' => $id1, 'name' => 'Test 1', 'territory' => 'test', 'code' => 'test' . $id1],
                ['id' => $id2, 'name' => 'Test 2', 'territory' => 'test', 'code' => 'test' . $id2],
            ],
            $context
        );

        $validator = $this->getValidator();

        $constraint = new All(
            [
                'constraints' => [
                    new EntityExists(
                        ['context' => $context, 'entity' => LocaleDefinition::ENTITY_NAME]
                    ),
                ],
            ]
        );

        $violations = $validator->validate([$id1, $id2], [$constraint]);

        // No violations as both entities exist.
        static::assertCount(0, $violations);

        $violations = $validator->validate([$id1, Uuid::randomHex(), $id2], [$constraint]);

        // One violation as one does not exist.
        static::assertCount(1, $violations);
    }

    protected function createRepository(): EntityRepository
    {
        $definition = static::getContainer()->get(LocaleDefinition::class);
        static::assertInstanceOf(LocaleDefinition::class, $definition);

        return new EntityRepository(
            $definition,
            static::getContainer()->get(EntityReaderInterface::class),
            static::getContainer()->get(VersionManager::class),
            static::getContainer()->get(EntitySearcherInterface::class),
            static::getContainer()->get(EntityAggregatorInterface::class),
            static::getContainer()->get(EventDispatcherInterface::class),
            static::getContainer()->get(EntityLoadedEventFactory::class)
        );
    }

    protected function getValidator(): ValidatorInterface
    {
        return $this->getValidatorBuilder()->getValidator();
    }

    protected function getValidatorBuilder(): ValidatorBuilder
    {
        return static::getContainer()->get('validator.builder');
    }
}
