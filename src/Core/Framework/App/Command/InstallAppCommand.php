<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Command;

use Cicada\Core\Framework\Adapter\Console\CicadaStyle;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Exception\AppAlreadyInstalledException;
use Cicada\Core\Framework\App\Exception\AppValidationException;
use Cicada\Core\Framework\App\Exception\UserAbortedCommandException;
use Cicada\Core\Framework\App\Lifecycle\AbstractAppLifecycle;
use Cicada\Core\Framework\App\Lifecycle\AppLoader;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Validation\ManifestValidator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal only for use by the app-system
 */
#[AsCommand(
    name: 'app:install',
    description: 'Installs an app',
)]
#[Package('core')]
class InstallAppCommand extends Command
{
    public function __construct(
        private readonly AppLoader $appLoader,
        private readonly AbstractAppLifecycle $appLifecycle,
        private readonly AppPrinter $appPrinter,
        private readonly ManifestValidator $manifestValidator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createCLIContext();
        $io = new CicadaStyle($input, $output);

        $names = $input->getArgument('name');

        if (\is_string($names)) {
            $names = [$names];
        }

        $manifests = $this->getMatchingManifests($names);
        $success = self::SUCCESS;

        if (\count($manifests) === 0) {
            $io->info('Could not find any app with this name');

            return self::SUCCESS;
        }

        foreach ($manifests as $name => $manifest) {
            if (!$input->getOption('force')) {
                try {
                    $this->checkPermissions($manifest, $io);

                    $this->appPrinter->checkHosts($manifest, $io);
                } catch (UserAbortedCommandException $e) {
                    $io->error('Aborting due to user input.');

                    return self::FAILURE;
                }
            }

            if (!$input->getOption('no-validate')) {
                try {
                    $this->manifestValidator->validate($manifest, $context);
                } catch (AppValidationException $e) {
                    $io->error(\sprintf('App installation of %s failed due: %s', $name, $e->getMessage()));

                    $success = self::FAILURE;

                    continue;
                }
            }

            try {
                $this->appLifecycle->install($manifest, $input->getOption('activate'), $context);
            } catch (AppAlreadyInstalledException) {
                $io->info(\sprintf('App %s is already installed', $name));

                continue;
            }

            $io->success(\sprintf('App %s has been successfully installed.', $name));
        }

        return (int) $success;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The name of the app')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force the installing of the app, it will automatically grant all requested permissions.'
            )
            ->addOption(
                'activate',
                'a',
                InputOption::VALUE_NONE,
                'Activate the app after installing it'
            )
            ->addOption(
                'no-validate',
                null,
                InputOption::VALUE_NONE,
                'Skip app validation.'
            );
    }

    /**
     * @param array<string> $requestedApps
     *
     * @return array<string, Manifest>
     */
    private function getMatchingManifests(array $requestedApps): array
    {
        $apps = $this->appLoader->load();
        $manifests = [];

        foreach ($requestedApps as $requestedApp) {
            foreach ($apps as $app => $manifest) {
                if (str_contains($app, $requestedApp)) {
                    $manifests[$app] = $manifest;
                }
            }
        }

        return $manifests;
    }

    private function checkPermissions(Manifest $manifest, CicadaStyle $io): void
    {
        if ($manifest->getPermissions()) {
            $this->appPrinter->printPermissions($manifest, $io, true);

            if (!$io->confirm(
                \sprintf('Do you want to grant these permissions for app "%s"?', $manifest->getMetadata()->getName()),
                false
            )) {
                throw AppException::userAborted();
            }
        }
    }
}
