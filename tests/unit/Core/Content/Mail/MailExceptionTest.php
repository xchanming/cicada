<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Mail;

use Cicada\Core\Content\Mail\MailException;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(MailException::class)]
class MailExceptionTest extends TestCase
{
    public function testGivenMailAgentIsInvalid(): void
    {
        $exception = MailException::givenMailAgentIsInvalid('john');

        static::assertInstanceOf(MailException::class, $exception);
        static::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getStatusCode());
        static::assertSame(MailException::GIVEN_AGENT_INVALID, $exception->getErrorCode());
        static::assertSame('Invalid mail agent given "john"', $exception->getMessage());
        static::assertSame(['agent' => 'john'], $exception->getParameters());
    }

    public function testGivenSendMailOptionIsInvalid(): void
    {
        $exception = MailException::givenSendMailOptionIsInvalid('blah', ['foo', 'bar']);

        static::assertInstanceOf(MailException::class, $exception);
        static::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getStatusCode());
        static::assertSame(MailException::GIVEN_OPTION_INVALID, $exception->getErrorCode());
        static::assertSame('Given sendmail option "blah" is invalid. Available options: foo, bar', $exception->getMessage());
        static::assertSame(['option' => 'blah', 'validOptions' => 'foo, bar'], $exception->getParameters());
    }

    public function testMailBodyTooLong(): void
    {
        $exception = MailException::mailBodyTooLong(5);

        static::assertInstanceOf(MailException::class, $exception);
        static::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        static::assertSame(MailException::MAIL_BODY_TOO_LONG, $exception->getErrorCode());
        static::assertSame('Mail body is too long. Maximum allowed length is 5', $exception->getMessage());
        static::assertSame(['maxContentLength' => 5], $exception->getParameters());
    }
}
