<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Command;

use Cicada\Core\Framework\App\AppStateService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @internal only for use by the app-system
 */
#[AsCommand(
    name: 'app:deactivate',
    description: 'Deactivates an app',
)]
#[Package('framework')]
class DeactivateAppCommand extends AbstractAppActivationCommand
{
    private const ACTION = 'deactivate';

    public function __construct(
        EntityRepository $appRepo,
        private readonly AppStateService $appStateService
    ) {
        parent::__construct($appRepo, self::ACTION);
    }

    public function runAction(string $appId, Context $context): void
    {
        $this->appStateService->deactivateApp($appId, $context);
    }
}
