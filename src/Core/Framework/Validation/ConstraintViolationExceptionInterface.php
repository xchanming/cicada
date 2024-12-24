<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Validation;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaException;
use Symfony\Component\Validator\ConstraintViolationList;

#[Package('core')]
interface ConstraintViolationExceptionInterface extends CicadaException
{
    public function getViolations(): ConstraintViolationList;
}
