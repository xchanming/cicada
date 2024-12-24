<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Command;

use Cicada\Core\Framework\Event\ProgressAdvancedEvent;
use Cicada\Core\Framework\Event\ProgressFinishedEvent;
use Cicada\Core\Framework\Event\ProgressStartedEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

#[Package('core')]
trait ConsoleProgressTrait
{
    /**
     * @var SymfonyStyle|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $io;

    /**
     * @var ProgressBar|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $progress;

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProgressStartedEvent::NAME => 'startProgress',
            ProgressAdvancedEvent::NAME => 'advanceProgress',
            ProgressFinishedEvent::NAME => 'finishProgress',
        ];
    }

    public function startProgress(ProgressStartedEvent $event): void
    {
        if (!$this->io) {
            return;
        }

        $this->progress = $this->io->createProgressBar($event->getTotal());
        $this->progress->setFormat("<info>[%message%]</info>\n%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%");
        $this->progress->setMessage($event->getMessage());
    }

    public function advanceProgress(ProgressAdvancedEvent $event): void
    {
        if (!$this->progress) {
            return;
        }

        $this->progress->advance($event->getStep());
    }

    public function finishProgress(ProgressFinishedEvent $event): void
    {
        if (!$this->io) {
            return;
        }

        if (!$this->progress) {
            return;
        }

        if (!$this->progress->getMaxSteps()) {
            return;
        }

        $this->progress->setMessage($event->getMessage());
        $this->progress->finish();
        $this->io->newLine(2);
    }
}
