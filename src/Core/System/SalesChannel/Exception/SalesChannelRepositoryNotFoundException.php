<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class SalesChannelRepositoryNotFoundException extends CicadaHttpException
{
    public function __construct(string $entity)
    {
        parent::__construct(
            'SalesChannelRepository for entity "{{ entityName }}" does not exist.',
            ['entityName' => $entity]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__SALES_CHANNEL_REPOSITORY_NOT_FOUND';
    }
}
