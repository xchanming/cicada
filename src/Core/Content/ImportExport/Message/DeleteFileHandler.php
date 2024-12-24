<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Message;

use Cicada\Core\Framework\Log\Package;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('services-settings')]
final class DeleteFileHandler
{
    /**
     * @internal
     */
    public function __construct(private readonly FilesystemOperator $filesystem)
    {
    }

    public function __invoke(DeleteFileMessage $message): void
    {
        foreach ($message->getFiles() as $file) {
            try {
                $this->filesystem->delete($file);
            } catch (UnableToDeleteFile) {
                // ignore file is already deleted
            }
        }
    }
}
