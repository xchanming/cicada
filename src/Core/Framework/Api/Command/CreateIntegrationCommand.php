<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Command;

use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'integration:create',
    description: 'Create an integration and dump the key and secret',
)]
#[Package('core')]
class CreateIntegrationCommand extends Command
{
    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $integrationRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the integration')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Has admin rights');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = AccessKeyHelper::generateAccessKey('integration');
        $secret = AccessKeyHelper::generateSecretAccessKey();

        $this->integrationRepository->create([
            [
                'label' => $input->getArgument('name'),
                'accessKey' => $id,
                'secretAccessKey' => $secret,
                'admin' => (bool) $input->getOption('admin'),
            ],
        ], Context::createCLIContext());

        $output->writeln('CICADA_ACCESS_KEY_ID=' . $id);
        $output->writeln('CICADA_SECRET_ACCESS_KEY=' . $secret);

        return self::SUCCESS;
    }
}
