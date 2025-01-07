<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Commands;

use Cicada\Core\Framework\Adapter\Console\CicadaStyle;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'media:delete-local-thumbnails',
    description: 'Deletes all physical media thumbnails when remote thumbnails is enabled.',
)]
#[Package('discovery')]
class DeleteThumbnailsCommand extends Command
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $thumbnailRepository,
        private readonly bool $remoteThumbnailsEnable = false
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new CicadaStyle($input, $output);

        if (!$this->remoteThumbnailsEnable) {
            $io->comment('Deleting thumbnails is only supported when remote thumbnail is enabled.');

            return self::FAILURE;
        }

        $this->deleteThumbnails();

        $io->success('Successfully deleted all thumbnails records and thumbnails files.');

        return self::SUCCESS;
    }

    private function deleteThumbnails(): void
    {
        $thumbnailIds = $this->connection->fetchAllAssociative('SELECT LOWER(HEX(`id`)) as id FROM `media_thumbnail`');

        $this->thumbnailRepository->delete($thumbnailIds, Context::createCLIContext());

        $this->connection->executeStatement('UPDATE `media` SET `thumbnails_ro` = NULL;');
    }
}
