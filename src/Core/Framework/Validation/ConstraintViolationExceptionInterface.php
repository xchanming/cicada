<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Validation;

use Cicada\Core\Framework\CicadaException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Validator\ConstraintViolationList;

#[Package('framework')]
interface ConstraintViolationExceptionInterface extends CicadaException
{
    public function getViolations(): ConstraintViolationList;
}
