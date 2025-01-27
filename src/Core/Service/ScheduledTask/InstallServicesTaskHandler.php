<?php declare(strict_types=1);

namespace Cicada\Core\Service\ScheduledTask;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Cicada\Core\Service\AllServiceInstaller;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('framework')]
#[AsMessageHandler(handles: InstallServicesTask::class)]
final class InstallServicesTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $repository,
        LoggerInterface $logger,
        private readonly AllServiceInstaller $serviceInstaller,
    ) {
        parent::__construct($repository, $logger);
    }

    public function run(): void
    {
        $this->serviceInstaller->install(Context::createCLIContext());
    }
}
