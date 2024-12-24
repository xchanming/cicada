<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Message;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

#[Package('services-settings')]
class DeleteFileMessage implements AsyncMessageInterface
{
    private array $files = [];

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): void
    {
        $this->files = $files;
    }
}
