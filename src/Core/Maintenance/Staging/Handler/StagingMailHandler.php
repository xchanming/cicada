<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\Staging\Handler;

use Cicada\Core\Content\Mail\Service\MailSender;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Maintenance\Staging\Event\SetupStagingEvent;
use Cicada\Core\System\SystemConfig\SystemConfigService;

/**
 * @internal
 */
#[Package('core')]
readonly class StagingMailHandler
{
    public function __construct(
        private bool $disableMailDelivery,
        private SystemConfigService $systemConfigService
    ) {
    }

    public function __invoke(SetupStagingEvent $event): void
    {
        if (!$this->disableMailDelivery) {
            return;
        }

        $this->systemConfigService->set(MailSender::DISABLE_MAIL_DELIVERY, true);

        $event->io->info('Disabled mail delivery.');
    }
}
