<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App;

use Cicada\Core\Framework\App\Event\AppActivatedEvent;
use Cicada\Core\Framework\App\Event\AppDeactivatedEvent;
use Cicada\Core\Framework\App\Event\Hooks\AppActivatedHook;
use Cicada\Core\Framework\App\Event\Hooks\AppDeactivatedHook;
use Cicada\Core\Framework\App\Lifecycle\Persister\FlowEventPersister;
use Cicada\Core\Framework\App\Lifecycle\Persister\RuleConditionPersister;
use Cicada\Core\Framework\App\Lifecycle\Persister\ScriptPersister;
use Cicada\Core\Framework\App\Payment\PaymentMethodStateService;
use Cicada\Core\Framework\App\Template\TemplateStateService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\ScriptExecutor;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class AppStateService
{
    /**
     * @param EntityRepository<AppCollection> $appRepo
     */
    public function __construct(
        private readonly EntityRepository $appRepo,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ActiveAppsLoader $activeAppsLoader,
        private readonly TemplateStateService $templateStateService,
        private readonly ScriptPersister $scriptPersister,
        private readonly PaymentMethodStateService $paymentMethodStateService,
        private readonly ScriptExecutor $scriptExecutor,
        private readonly RuleConditionPersister $ruleConditionPersister,
        private readonly FlowEventPersister $flowEventPersister
    ) {
    }

    public function activateApp(string $appId, Context $context): void
    {
        $app = $this->appRepo->search(new Criteria([$appId]), $context)->getEntities()->first();

        if ($app === null) {
            throw AppException::notFound($appId);
        }
        if ($app->isActive()) {
            return;
        }

        $this->appRepo->update([['id' => $appId, 'active' => true]], $context);
        $this->templateStateService->activateAppTemplates($appId, $context);
        $this->scriptPersister->activateAppScripts($appId, $context);
        $this->paymentMethodStateService->activatePaymentMethods($appId, $context);
        $this->ruleConditionPersister->activateConditionScripts($appId, $context);
        $this->activeAppsLoader->reset();
        // manually set active flag to true, so we don't need to re-fetch the app from DB
        $app->setActive(true);

        $event = new AppActivatedEvent($app, $context);
        $this->eventDispatcher->dispatch($event);
        $this->scriptExecutor->execute(new AppActivatedHook($event));
    }

    public function deactivateApp(string $appId, Context $context): void
    {
        $app = $this->appRepo->search(new Criteria([$appId]), $context)->getEntities()->first();

        if ($app === null) {
            throw AppException::notFound($appId);
        }
        if (!$app->isActive()) {
            return;
        }
        if (!$app->getAllowDisable()) {
            throw new \RuntimeException(\sprintf('App %s can not be deactivated. You have to uninstall the app.', $app->getName()));
        }

        $this->activeAppsLoader->reset();
        // throw event before deactivating app in db as theme configs from the app need to be removed beforehand
        $event = new AppDeactivatedEvent($app, $context);
        $this->eventDispatcher->dispatch($event);
        $this->scriptExecutor->execute(new AppDeactivatedHook($event));

        $this->appRepo->update([['id' => $appId, 'active' => false]], $context);
        $this->templateStateService->deactivateAppTemplates($appId, $context);
        $this->scriptPersister->deactivateAppScripts($appId, $context);
        $this->paymentMethodStateService->deactivatePaymentMethods($appId, $context);
        $this->ruleConditionPersister->deactivateConditionScripts($appId, $context);
        $this->flowEventPersister->deactivateFlow($appId);
    }
}
