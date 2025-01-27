<?php declare(strict_types=1);

namespace Cicada\Administration\Controller\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
class MissingAppSecretException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Failed to retrieve app secret.');
    }

    public function getErrorCode(): string
    {
        return 'ADMINISTRATION__MISSING_APP_SECRET';
    }
}
