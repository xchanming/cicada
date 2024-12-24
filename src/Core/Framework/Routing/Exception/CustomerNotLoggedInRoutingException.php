<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Routing\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;

#[Package('checkout')]
class CustomerNotLoggedInRoutingException extends RoutingException
{
}
