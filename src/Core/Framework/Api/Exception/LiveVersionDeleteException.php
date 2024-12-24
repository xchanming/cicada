<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class LiveVersionDeleteException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Live version can not be deleted. Delete entity instead.');
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__LIVE_VERSION_DELETE';
    }
}
