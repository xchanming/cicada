<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Exception;

use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SystemConfig\Exception\XmlParsingException;

/**
 * @deprecated tag:v6.7.0 - will be removed, use AppException::errorFlowCreateFromXmlFile instead
 */
#[Package('core')]
class AppFlowException extends XmlParsingException
{
    public function __construct(
        string $xmlFile,
        string $message
    ) {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'AppException::errorFlowCreateFromXmlFile')
        );

        parent::__construct(
            $xmlFile,
            $message
        );
    }
}
