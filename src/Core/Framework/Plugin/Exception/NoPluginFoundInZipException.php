<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class NoPluginFoundInZipException extends CicadaHttpException
{
    public function __construct(string $archive)
    {
        parent::__construct(
            'No plugin was found in the zip archive: {{ archive }}',
            ['archive' => $archive]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__PLUGIN_NO_PLUGIN_FOUND_IN_ZIP';
    }
}
