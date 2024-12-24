<?php declare(strict_types=1);

namespace Cicada\Core\Test\Stub\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;

/**
 * @internal
 */
class EmptyEntityExistence extends EntityExistence
{
    public function __construct()
    {
        parent::__construct('', [], true, false, false, []);
    }
}
