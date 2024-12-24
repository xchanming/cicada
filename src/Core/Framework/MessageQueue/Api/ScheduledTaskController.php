<?php declare(strict_types=1);

namespace Cicada\Core\Framework\MessageQueue\Api;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\Scheduler\TaskScheduler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
class ScheduledTaskController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(private readonly TaskScheduler $taskScheduler)
    {
    }

    #[Route(path: '/api/_action/scheduled-task/run', name: 'api.action.scheduled-task.run', methods: ['POST'])]
    public function runScheduledTasks(): JsonResponse
    {
        $this->taskScheduler->queueScheduledTasks();

        return new JsonResponse(['message' => 'Success']);
    }

    #[Route(path: '/api/_action/scheduled-task/min-run-interval', name: 'api.action.scheduled-task.min-run-interval', methods: ['GET'])]
    public function getMinRunInterval(): JsonResponse
    {
        return new JsonResponse(['minRunInterval' => $this->taskScheduler->getMinRunInterval()]);
    }
}
