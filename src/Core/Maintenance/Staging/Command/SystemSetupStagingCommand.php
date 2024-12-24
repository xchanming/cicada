<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\Staging\Command;

use Psr\EventDispatcher\EventDispatcherInterface;
use Cicada\Core\Framework\Adapter\Console\CicadaStyle;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Maintenance\Staging\Event\SetupStagingEvent;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'system:setup:staging',
    description: 'Installs the Cicada 6 system in staging mode',
)]
#[Package('core')]
class SystemSetupStagingCommand extends Command
{
    public function __construct(
        readonly private EventDispatcherInterface $eventDispatcher,
        readonly private SystemConfigService $systemConfigService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force setup of staging system');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new CicadaStyle($input, $output);

        if (!$input->getOption('force') && !$io->confirm('This command will install the Cicada 6 system in staging mode. It will overwrite existing data in this database, make sure you use a staging database and have a backup', false)) {
            return self::FAILURE;
        }

        $event = new SetupStagingEvent(Context::createCLIContext(), $io);
        $this->eventDispatcher->dispatch($event);

        $this->systemConfigService->set(SetupStagingEvent::CONFIG_FLAG, true);

        return $event->canceled ? self::FAILURE : self::SUCCESS;
    }
}
