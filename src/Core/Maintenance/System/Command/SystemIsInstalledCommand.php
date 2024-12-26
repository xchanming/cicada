<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\System\Command;

use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command can be used to detect if the system is installed to script a Cicada installation or update.
 *
 * @internal
 */
#[Package('core')]
#[AsCommand(
    name: 'system:is-installed',
    description: 'Checks if the system is installed and returns exit code 0 if Cicada is installed',
)]
class SystemIsInstalledCommand extends Command
{
    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->connection->fetchAllAssociative('SHOW COLUMNS FROM migration');

            $io->success('Cicada is installed');

            return self::SUCCESS;
        } catch (\Exception) {
            $io->error('Cicada is not installed');

            return self::FAILURE;
        }
    }
}
