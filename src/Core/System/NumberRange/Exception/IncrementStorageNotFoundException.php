<?php declare(strict_types=1);

namespace Cicada\Core\System\NumberRange\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class IncrementStorageNotFoundException extends CicadaHttpException
{
    /**
     * @param array<string> $availableStorages
     */
    public function __construct(
        string $configuredStorage,
        array $availableStorages
    ) {
        parent::__construct(
            'The number range increment storage "{{ configuredStorage }}" is not available. Available storages are: "{{ availableStorages }}".',
            [
                'configuredStorage' => $configuredStorage,
                'availableStorages' => implode('", "', $availableStorages),
            ]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INCREMENT_STORAGE_NOT_FOUND';
    }
}
