<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Payment\Response;

use Cicada\Core\Framework\App\Payment\Response\AbstractResponse;
use Cicada\Core\Framework\App\Payment\Response\ValidateResponse;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ValidateResponse::class)]
#[CoversClass(AbstractResponse::class)]
class ValidateResponseTest extends TestCase
{
    public function testEmpty(): void
    {
        $response = new ValidateResponse();
        static::assertSame([], $response->getPreOrderPayment());
    }

    public function testNonEmpty(): void
    {
        $response = new ValidateResponse();
        $response->assign([
            'preOrderPayment' => [
                'foo' => 'bar',
            ],
        ]);
        static::assertSame(['foo' => 'bar'], $response->getPreOrderPayment());
    }
}
