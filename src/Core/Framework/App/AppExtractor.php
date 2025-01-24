<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App;

use Cicada\Core\Framework\App\Exception\AppArchiveValidationFailure;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginException;
use Cicada\Core\Framework\Plugin\Util\ZipUtils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 *
 * @codeCoverageIgnore Integration tested with \Cicada\Tests\Integration\Core\Framework\App\AppExtractorTest
 */
#[Package('framework')]
class AppExtractor
{
    public function __construct(
        private readonly AppArchiveValidator $appArchiveValidator,
        private readonly Filesystem $filesystem = new Filesystem()
    ) {
    }

    /**
     * If we know the expected app name we can perform extra validation.
     *
     * @throws PluginException
     * @throws AppArchiveValidationFailure
     *
     * @return string The path to the app
     *
     **/
    public function extract(string $zipLocation, string $destinationDirectory, ?string $appName = null): string
    {
        $this->filesystem->mkdir($destinationDirectory);

        $archive = ZipUtils::openZip($zipLocation);

        $this->appArchiveValidator->validate($archive, $appName);
        $appName = $this->getAppName($archive);
        $archive->extractTo($destinationDirectory);

        return Path::join($destinationDirectory, $appName);
    }

    private function getAppName(\ZipArchive $archive): string
    {
        return explode('/', $archive->statIndex(0)['name'] ?? '')[0];
    }
}
