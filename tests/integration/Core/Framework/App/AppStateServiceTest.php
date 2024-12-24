<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppStateService;
use Cicada\Core\Framework\App\Event\AppActivatedEvent;
use Cicada\Core\Framework\App\Event\AppDeactivatedEvent;
use Cicada\Core\Framework\App\Event\Hooks\AppActivatedHook;
use Cicada\Core\Framework\App\Event\Hooks\AppDeactivatedHook;
use Cicada\Core\Framework\App\Exception\AppNotFoundException;
use Cicada\Core\Framework\App\Lifecycle\AbstractAppLifecycle;
use Cicada\Core\Framework\App\Lifecycle\AppLifecycle;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\Constraint\ArrayOfUuid;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
class AppStateServiceTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<AppCollection>
     */
    private EntityRepository $appRepository;

    private AppStateService $appStateService;

    private EventDispatcherInterface $eventDispatcher;

    private AbstractAppLifecycle $appLifecycle;

    private Context $context;

    protected function setUp(): void
    {
        $this->appRepository = static::getContainer()->get('app.repository');
        $this->appStateService = static::getContainer()->get(AppStateService::class);
        $this->eventDispatcher = static::getContainer()->get('event_dispatcher');
        $this->appLifecycle = static::getContainer()->get(AppLifecycle::class);
        $this->context = Context::createDefaultContext();
    }

    public function testNotFoundAppThrowsOnActivate(): void
    {
        $this->expectException(AppNotFoundException::class);
        $this->appStateService->activateApp(Uuid::randomHex(), Context::createDefaultContext());
    }

    public function testNotFoundAppThrowsOnDeactivate(): void
    {
        $this->expectException(AppNotFoundException::class);
        $this->appStateService->deactivateApp(Uuid::randomHex(), Context::createDefaultContext());
    }

    public function testActivate(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/Manifest/_fixtures/test/manifest.xml');
        $this->appLifecycle->install($manifest, false, $this->context);
        $appId = $this->appRepository->searchIds(new Criteria(), $this->context)->firstId();
        static::assertNotNull($appId);
        $this->assertAppState($appId, false);

        $eventWasReceived = false;
        $onAppInstalled = function (AppActivatedEvent $event) use ($appId, &$eventWasReceived): void {
            $eventWasReceived = true;
            static::assertSame($appId, $event->getApp()->getId());
        };
        $this->eventDispatcher->addListener(AppActivatedEvent::class, $onAppInstalled);
        $this->appStateService->activateApp($appId, $this->context);

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();
        static::assertArrayHasKey(AppActivatedHook::HOOK_NAME, $traces);
        static::assertSame('activated', $traces[AppActivatedHook::HOOK_NAME][0]['output'][0]);

        static::assertTrue($eventWasReceived);
        $this->eventDispatcher->removeListener(AppActivatedEvent::class, $onAppInstalled);

        $this->assertAppState($appId, true);
    }

    public function testDeactivate(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/Manifest/_fixtures/test/manifest.xml');
        $this->appLifecycle->install($manifest, true, $this->context);
        $appId = $this->appRepository->searchIds(new Criteria(), $this->context)->firstId();
        static::assertNotNull($appId);
        $this->assertAppState($appId, true);

        $eventWasReceived = false;
        $onAppInstalled = function (AppDeactivatedEvent $event) use ($appId, &$eventWasReceived): void {
            $eventWasReceived = true;
            static::assertSame($appId, $event->getApp()->getId());
        };
        $this->eventDispatcher->addListener(AppDeactivatedEvent::class, $onAppInstalled);
        $this->appStateService->deactivateApp($appId, $this->context);

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();
        static::assertArrayHasKey(AppDeactivatedHook::HOOK_NAME, $traces);
        static::assertSame('deactivated', $traces[AppDeactivatedHook::HOOK_NAME][0]['output'][0]);

        static::assertTrue($eventWasReceived);
        $this->eventDispatcher->removeListener(AppDeactivatedEvent::class, $onAppInstalled);

        $this->assertAppState($appId, false);
    }

    public function testDeactivateThrowsIfDeactivationIsNotAllowed(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/Manifest/_fixtures/test/manifest.xml');
        $this->appLifecycle->install($manifest, true, $this->context);
        $appId = $this->appRepository->searchIds(new Criteria(), $this->context)->firstId();
        static::assertNotNull($appId);
        $this->assertAppState($appId, true);
        $this->appRepository->update([
            [
                'id' => $appId,
                'allowDisable' => false,
            ],
        ], $this->context);

        $this->expectException(\RuntimeException::class);
        $this->appStateService->deactivateApp($appId, $this->context);
    }

    private function assertAppState(?string $appId, bool $active): void
    {
        static::assertNotNull($appId);

        $criteria = new Criteria([$appId]);
        $criteria->addAssociation('templates');
        $criteria->addAssociation('paymentMethods.paymentMethod');
        $criteria->addAssociation('scripts');
        $criteria->addAssociation('scriptConditions');

        $app = $this->appRepository->search($criteria, $this->context)->getEntities()->first();
        static::assertNotNull($app);
        static::assertSame($active, $app->isActive());
        $this->assertDefaultTemplate($app);
        $this->assertDefaultPaymentMethods($app);
        $this->assertDefaultScripts($app);
        $this->assertDefaultScriptConditions($app);
    }

    private function assertDefaultTemplate(AppEntity $app): void
    {
        static::assertNotNull($app->getTemplates());
        $template = $app->getTemplates()->first();

        static::assertNotNull($template);
        static::assertSame($app->isActive(), $template->isActive());
    }

    private function assertDefaultPaymentMethods(AppEntity $app): void
    {
        static::assertNotNull($app->getPaymentMethods());

        static::assertCount(2, $app->getPaymentMethods());
        foreach ($app->getPaymentMethods() as $appPaymentMethod) {
            $paymentMethod = $appPaymentMethod->getPaymentMethod();
            static::assertNotNull($paymentMethod);
            static::assertSame($app->isActive(), $paymentMethod->getActive());
        }
    }

    private function assertDefaultScripts(AppEntity $app): void
    {
        static::assertNotNull($app->getScripts());
        $script = $app->getScripts()->first();
        static::assertNotNull($script);
        static::assertSame($app->isActive(), $script->isActive());
    }

    private function assertDefaultScriptConditions(AppEntity $app): void
    {
        static::assertNotNull($app->getScriptConditions());
        $scriptCondition = $app->getScriptConditions()->first();
        static::assertNotNull($scriptCondition);
        static::assertSame($app->isActive(), $scriptCondition->isActive());
        $script = $scriptCondition->getScript();
        static::assertIsString($script);
        static::assertStringEqualsFile(
            __DIR__ . '/Manifest/_fixtures/test/Resources/scripts/rule-conditions/customer-group-rule-script.twig',
            $script
        );
        static::assertIsArray($scriptCondition->getConstraints());
        static::assertArrayHasKey('operator', $scriptCondition->getConstraints());
        static::assertArrayHasKey('customerGroupIds', $scriptCondition->getConstraints());
        static::assertInstanceOf(NotBlank::class, $scriptCondition->getConstraints()['operator'][0]);
        static::assertInstanceOf(NotBlank::class, $scriptCondition->getConstraints()['customerGroupIds'][0]);
        static::assertInstanceOf(Choice::class, $scriptCondition->getConstraints()['operator'][1]);
        static::assertInstanceOf(ArrayOfUuid::class, $scriptCondition->getConstraints()['customerGroupIds'][1]);
        static::assertSame(['=', '!='], $scriptCondition->getConstraints()['operator'][1]->choices);
    }
}
