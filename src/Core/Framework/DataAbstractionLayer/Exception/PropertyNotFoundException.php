<?php

declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class PropertyNotFoundException extends DataAbstractionLayerException
{
    public function __construct(string $property, string $entityClassName)
    {
        parent::__construct(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::PROPERTY_NOT_FOUND,
            'Property "{{ property }}" does not exist in entity "{{ entityClassName }}".',
            ['property' => $property, 'entityClassName' => $entityClassName]
        );
    }
}
