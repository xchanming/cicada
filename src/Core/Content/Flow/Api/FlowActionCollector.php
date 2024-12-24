<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Api;

use Cicada\Core\Content\Flow\Dispatching\Action\FlowAction;
use Cicada\Core\Content\Flow\Dispatching\DelayableAction;
use Cicada\Core\Content\Flow\Events\FlowActionCollectorEvent;
use Cicada\Core\Framework\App\Aggregate\FlowAction\AppFlowActionEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('services-settings')]
class FlowActionCollector
{
    /**
     * @internal
     *
     * @param iterable<FlowAction> $actions
     */
    public function __construct(
        protected iterable $actions,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityRepository $appFlowActionRepo
    ) {
    }

    public function collect(Context $context): FlowActionCollectorResponse
    {
        $result = new FlowActionCollectorResponse();

        $result = $this->fetchAppActions($result, $context);

        foreach ($this->actions as $service) {
            if (!$service instanceof FlowAction) {
                continue;
            }

            $definition = $this->define($service);

            if (!$result->has($definition->getName())) {
                $result->set($definition->getName(), $definition);
            }
        }

        $this->eventDispatcher->dispatch(new FlowActionCollectorEvent($result, $context));

        return $result;
    }

    private function fetchAppActions(FlowActionCollectorResponse $result, Context $context): FlowActionCollectorResponse
    {
        $criteria = new Criteria();
        $appActions = $this->appFlowActionRepo->search($criteria, $context)->getEntities();

        /** @var AppFlowActionEntity $action */
        foreach ($appActions as $action) {
            $definition = new FlowActionDefinition(
                $action->getName(),
                $action->getRequirements(),
                $action->getDelayable()
            );

            if (!$result->has($definition->getName())) {
                $result->set($definition->getName(), $definition);
            }
        }

        return $result;
    }

    private function define(FlowAction $service): FlowActionDefinition
    {
        $requirementsName = [];
        foreach ($service->requirements() as $requirement) {
            $className = explode('\\', $requirement);
            $requirementsName[] = lcfirst(end($className));
        }

        $delayable = false;
        if ($service instanceof DelayableAction) {
            $delayable = true;
        }

        return new FlowActionDefinition(
            $service->getName(),
            $requirementsName,
            $delayable
        );
    }
}
