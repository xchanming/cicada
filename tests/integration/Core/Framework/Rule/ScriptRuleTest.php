<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Framework\App\Aggregate\AppScriptCondition\AppScriptConditionCollection;
use Cicada\Core\Framework\App\Aggregate\AppScriptCondition\AppScriptConditionEntity;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppStateService;
use Cicada\Core\Framework\App\Lifecycle\AbstractAppLifecycle;
use Cicada\Core\Framework\App\Lifecycle\AppLifecycle;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\ScriptRule;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
#[Package('services-settings')]
#[RunTestsInSeparateProcesses]
class ScriptRuleTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<RuleCollection>
     */
    private EntityRepository $ruleRepository;

    private EntityRepository $conditionRepository;

    /**
     * @var EntityRepository<AppCollection>
     */
    private EntityRepository $appRepository;

    private AppStateService $appStateService;

    private AbstractAppLifecycle $appLifecycle;

    private Context $context;

    private string $scriptId;

    private string $appId;

    protected function setUp(): void
    {
        $this->ruleRepository = static::getContainer()->get('rule.repository');
        $this->conditionRepository = static::getContainer()->get('rule_condition.repository');
        $this->appRepository = static::getContainer()->get('app.repository');
        $this->appStateService = static::getContainer()->get(AppStateService::class);
        $this->appLifecycle = static::getContainer()->get(AppLifecycle::class);
        $this->context = Context::createDefaultContext();
    }

    /**
     * @param array<string, string> $values
     */
    #[DataProvider('scriptProvider')]
    public function testRuleScriptExecution(string $path, array $values, bool $expectedTrue): void
    {
        $script = file_get_contents(__DIR__ . $path);
        $scope = new CheckoutRuleScope($this->createSalesChannelContext());
        $rule = new ScriptRule();

        $rule->assign([
            'values' => $values,
            'script' => $script,
            'debug' => false,
            'cacheDir' => static::getContainer()->getParameter('kernel.cache_dir'),
        ]);

        if ($expectedTrue) {
            static::assertTrue($rule->match($scope));
        } else {
            static::assertFalse($rule->match($scope));
        }
    }

    public static function scriptProvider(): \Generator
    {
        yield 'simple script return true' => ['/_fixture/scripts/simple.twig', ['test' => 'foo'], true];
        yield 'simple script return false' => ['/_fixture/scripts/simple.twig', ['test' => 'bar'], false];
    }

    #[Depends('testRuleScriptExecution')]
    public function testRuleScriptIsCached(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $scope = new CheckoutRuleScope($salesChannelContext);
        $rule = new ScriptRule();

        $rule->assign([
            'script' => '{% return true %}',
            'values' => [],
            'lastModified' => (new \DateTimeImmutable())->sub(new \DateInterval('P1D')),
            'debug' => false,
            'cacheDir' => static::getContainer()->getParameter('kernel.cache_dir'),
        ]);

        static::assertFalse($rule->match($scope));
    }

    #[Depends('testRuleScriptIsCached')]
    public function testCachedRuleScriptIsInvalidated(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $scope = new CheckoutRuleScope($salesChannelContext);
        $rule = new ScriptRule();

        $rule->assign([
            'script' => '{% return true %}',
            'values' => [],
            'debug' => false,
            'cacheDir' => static::getContainer()->getParameter('kernel.cache_dir'),
        ]);

        static::assertTrue($rule->match($scope));
    }

    public function testRuleIsConsistent(): void
    {
        $this->installApp();
        $ruleId = Uuid::randomHex();
        $conditionId = Uuid::randomHex();
        $expectedTrueScope = $this->getCheckoutScope($ruleId, $conditionId);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $customer = new CustomerEntity();
        $customer->setGroupId(Uuid::randomHex());
        $salesChannelContext->method('getCustomer')->willReturn($customer);
        $expectedFalseScope = new CheckoutRuleScope($salesChannelContext);

        $rule = $this->ruleRepository->search(new Criteria([$ruleId]), $this->context)->getEntities()->get($ruleId);
        static::assertInstanceOf(RuleEntity::class, $rule);
        $payload = $rule->getPayload();
        static::assertInstanceOf(Rule::class, $payload);
        static::assertFalse($payload->match($expectedFalseScope));
        static::assertTrue($payload->match($expectedTrueScope));

        $this->ruleRepository->delete([['id' => $ruleId]], $this->context);
        $this->conditionRepository->delete([['id' => $conditionId]], $this->context);
    }

    public function testRuleValidationFails(): void
    {
        $this->installApp();

        try {
            $ruleId = Uuid::randomHex();
            $this->ruleRepository->create(
                [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
                Context::createDefaultContext()
            );

            $id = Uuid::randomHex();
            $this->conditionRepository->create([
                [
                    'id' => $id,
                    'type' => (new ScriptRule())->getName(),
                    'ruleId' => $ruleId,
                    'scriptId' => $this->scriptId,
                    'value' => [
                        'operator' => 'foo',
                    ],
                ],
            ], $this->context);

            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors(), false);
            static::assertCount(2, $exceptions);
            static::assertSame('/0/value/operator', $exceptions[0]['source']['pointer']);
            static::assertSame(Choice::NO_SUCH_CHOICE_ERROR, $exceptions[0]['code']);
            static::assertSame('/0/value/customerGroupIds', $exceptions[1]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[1]['code']);
        }
    }

    public static function manifestPathProvider(): \Generator
    {
        yield 'Default fixture App with customerGroupIds property' => [
            '/test/manifest.xml',
            [
                'operator' => '=',
                'customerGroupIds' => [Uuid::randomHex()],
            ],
        ];

        yield 'App with firstName as rule property' => [
            '/test/manifest_arbitraryRule_firstName.xml',
            [
                'operator' => '=',
                'firstName' => 'hello',
            ],
        ];

        yield 'App with existing constraints name as rule property' => [
            '/test/manifest_arbitraryRule_constraints.xml',
            [
                'operator' => '=',
                'constraints' => 'broken',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $value
     */
    #[DataProvider('manifestPathProvider')]
    public function testRuleValidationSucceedsWithArbitraryProperties(string $manifestPath, array $value): void
    {
        $fixturesPath = __DIR__ . '/../App/Manifest/_fixtures';
        $manifest = Manifest::createFromXmlFile($fixturesPath . $manifestPath);
        $this->setupApp($manifest);

        $ruleId = Uuid::randomHex();
        $this->ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            $this->context
        );

        $id = Uuid::randomHex();
        $this->conditionRepository->create([
            [
                'id' => $id,
                'type' => (new ScriptRule())->getName(),
                'ruleId' => $ruleId,
                'scriptId' => $this->scriptId,
                'value' => $value,
            ],
        ], $this->context);

        $rule = $this->ruleRepository->search(new Criteria([$ruleId]), $this->context)->getEntities()->get($ruleId);
        static::assertInstanceOf(RuleEntity::class, $rule);
        $payload = $rule->getPayload();
        static::assertInstanceOf(AndRule::class, $payload);

        $scriptRule = $payload->getRules()[0];
        static::assertInstanceOf(ScriptRule::class, $scriptRule);
        static::assertSame($value, $scriptRule->getValues());
        static::assertSame([], $scriptRule->getConstraints());

        $this->ruleRepository->delete([['id' => $ruleId]], $this->context);
        $this->conditionRepository->delete([['id' => $id]], $this->context);
    }

    public function testRuleWithInactiveScript(): void
    {
        $this->installApp();
        $ruleId = Uuid::randomHex();
        $conditionId = Uuid::randomHex();
        $scope = $this->getCheckoutScope($ruleId, $conditionId);

        $this->appStateService->deactivateApp($this->appId, $this->context);

        $rule = $this->ruleRepository->search(new Criteria([$ruleId]), $this->context)->getEntities()->get($ruleId);
        static::assertInstanceOf(RuleEntity::class, $rule);
        $payload = $rule->getPayload();
        static::assertInstanceOf(Rule::class, $payload);
        static::assertFalse($payload->match($scope));

        $this->appStateService->activateApp($this->appId, $this->context);

        $rule = $this->ruleRepository->search(new Criteria([$ruleId]), $this->context)->getEntities()->get($ruleId);
        static::assertInstanceOf(RuleEntity::class, $rule);
        $payload = $rule->getPayload();
        static::assertInstanceOf(Rule::class, $payload);
        static::assertTrue($payload->match($scope));

        $this->ruleRepository->delete([['id' => $ruleId]], $this->context);
        $this->conditionRepository->delete([['id' => $conditionId]], $this->context);
    }

    public function testRuleWithUninstalledApp(): void
    {
        $this->installApp();
        $ruleId = Uuid::randomHex();
        $conditionId = Uuid::randomHex();
        $scope = $this->getCheckoutScope($ruleId, $conditionId);

        $rule = $this->ruleRepository->search(new Criteria([$ruleId]), $this->context)->getEntities()->get($ruleId);
        static::assertInstanceOf(RuleEntity::class, $rule);
        $payload = $rule->getPayload();
        static::assertInstanceOf(Rule::class, $payload);
        static::assertTrue($payload->match($scope));

        $this->appLifecycle->delete('test', ['id' => $this->appId], $this->context);

        $rule = $this->ruleRepository->search(new Criteria([$ruleId]), $this->context)->getEntities()->get($ruleId);
        static::assertInstanceOf(RuleEntity::class, $rule);
        $payload = $rule->getPayload();
        static::assertInstanceOf(Rule::class, $payload);
        static::assertFalse($payload->match($scope));

        $this->ruleRepository->delete([['id' => $ruleId]], $this->context);
        $this->conditionRepository->delete([['id' => $conditionId]], $this->context);
    }

    public function testRuleValueAssignment(): void
    {
        $rule = new ScriptRule();
        $value = [
            'operator' => '=',
            'customerGroupIds' => [Uuid::randomHex()],
        ];
        $rule->assignValues($value);

        static::assertSame($value, $rule->getValues());
    }

    private function getCheckoutScope(string $ruleId, string $conditionId): CheckoutRuleScope
    {
        $this->ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            $this->context
        );

        $groupId = Uuid::randomHex();
        $this->conditionRepository->create([
            [
                'id' => $conditionId,
                'type' => (new ScriptRule())->getName(),
                'ruleId' => $ruleId,
                'scriptId' => $this->scriptId,
                'value' => [
                    'customerGroupIds' => [Uuid::randomHex(), $groupId],
                    'operator' => Rule::OPERATOR_EQ,
                ],
            ],
        ], $this->context);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $customer = new CustomerEntity();

        $customer->setGroupId($groupId);
        $salesChannelContext->method('getCustomer')->willReturn($customer);

        return new CheckoutRuleScope($salesChannelContext);
    }

    private function installApp(): void
    {
        $fixturesPath = __DIR__ . '/../App/Manifest/_fixtures';

        $manifest = Manifest::createFromXmlFile($fixturesPath . '/test/manifest.xml');
        $this->setupApp($manifest);
    }

    private function setupApp(Manifest $manifest): void
    {
        $this->appLifecycle->install($manifest, false, $this->context);

        $app = $this->appRepository->search((new Criteria())->addAssociation('scriptConditions'), $this->context)->first();
        static::assertInstanceOf(AppEntity::class, $app);
        $this->appId = $app->getId();
        $this->appStateService->activateApp($this->appId, $this->context);
        $conditions = $app->getScriptConditions();
        static::assertInstanceOf(AppScriptConditionCollection::class, $conditions);
        $condition = $conditions->first();
        static::assertInstanceOf(AppScriptConditionEntity::class, $condition);
        $this->scriptId = $condition->getId();
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        $salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class);

        return $salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }
}
