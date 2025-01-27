<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Event;

use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('fundamentals@after-sales')]
class ImportExportBeforeImportRecordEvent extends Event
{
    public function __construct(
        private array $record,
        private readonly array $row,
        private readonly Config $config,
        private readonly Context $context
    ) {
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getRow(): array
    {
        return $this->row;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
