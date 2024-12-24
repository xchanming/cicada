<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Write\FieldException;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaException;

#[Package('core')]
interface WriteFieldException extends CicadaException
{
    public function getPath(): string;
}
