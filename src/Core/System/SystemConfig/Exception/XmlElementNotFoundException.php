<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig\Exception;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\UtilException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - will be removed, use UtilException::xmlElementNotFound instead
 */
#[Package('services-settings')]
class XmlElementNotFoundException extends UtilException
{
    public function __construct(string $element)
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'AppException::xmlParsingException')
        );

        parent::__construct(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::XML_ELEMENT_NOT_FOUND,
            'Unable to locate element with the name "{{ element }}".',
            ['element' => $element]
        );
    }
}
