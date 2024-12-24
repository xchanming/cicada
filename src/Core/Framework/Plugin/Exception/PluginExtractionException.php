<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class PluginExtractionException extends CicadaHttpException
{
    public function __construct(string $reason)
    {
        parent::__construct(
            'Plugin extraction failed. Error: {{ error }}',
            ['error' => $reason]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__PLUGIN_EXTRACTION_FAILED';
    }
}
