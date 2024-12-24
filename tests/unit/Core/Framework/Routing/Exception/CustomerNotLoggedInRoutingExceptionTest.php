<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Routing\Exception;

use Cicada\Core\Framework\Routing\Exception\CustomerNotLoggedInRoutingException;
use Cicada\Core\Framework\Routing\RoutingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[CoversClass(CustomerNotLoggedInRoutingException::class)]
class CustomerNotLoggedInRoutingExceptionTest extends TestCase
{
    public function testException(): void
    {
        $this->expectException(CustomerNotLoggedInRoutingException::class);
        $this->expectExceptionMessage('Customer is not logged in.');

        throw new CustomerNotLoggedInRoutingException(Response::HTTP_FORBIDDEN, RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, 'Customer is not logged in.');
    }
}
