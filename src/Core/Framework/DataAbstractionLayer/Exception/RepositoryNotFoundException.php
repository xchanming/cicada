<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class RepositoryNotFoundException extends CicadaHttpException
{
    public function __construct(string $entity)
    {
        parent::__construct('Repository for entity "{{ entityName }}" does not exist.', ['entityName' => $entity]);
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__REPOSITORY_NOT_FOUND';
    }
}
