<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class UnexpectedFieldConfigValueType extends CicadaHttpException
{
    public function __construct(
        string $fieldConfigName,
        string $expectedType,
        string $givenType
    ) {
        parent::__construct(
            'Expected to load value of "{{ fieldConfigName }}" with type "{{ expectedType }}", but value with type "{{ givenType }}" given.',
            [
                'fieldConfigName' => $fieldConfigName,
                'expectedType' => $expectedType,
                'givenType' => $givenType,
            ]
        );
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__CMS_UNEXPECTED_VALUE_TYPE';
    }
}
