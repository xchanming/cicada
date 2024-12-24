<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Maintenance\User\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Maintenance\User\Command\UserChangePasswordCommand;
use Cicada\Core\System\User\UserCollection;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(UserChangePasswordCommand::class)]
class UserChangePasswordCommandTest extends TestCase
{
    private const TEST_USERNAME = 'cicada';
    private const TEST_PASSWORD = 'cicadaPassword';

    public function testUnknownUser(): void
    {
        /** @var StaticEntityRepository<UserCollection> $userRepo */
        $userRepo = new StaticEntityRepository([[]]);
        $command = new UserChangePasswordCommand($userRepo);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'username' => self::TEST_USERNAME,
            '--password' => self::TEST_PASSWORD,
        ]);

        $expected = 'The user "' . self::TEST_USERNAME . '" does not exist.';
        static::assertStringContainsString($expected, $commandTester->getDisplay());
        static::assertSame(1, $commandTester->getStatusCode());
    }

    public function testKnownUser(): void
    {
        $userId = Uuid::randomHex();
        $newPassword = Uuid::randomHex();

        /** @var StaticEntityRepository<UserCollection> $userRepo */
        $userRepo = new StaticEntityRepository([[$userId]]);
        $command = new UserChangePasswordCommand($userRepo);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'username' => self::TEST_USERNAME,
            '--password' => $newPassword,
        ]);

        $expected = 'The password of user "' . self::TEST_USERNAME . '" has been changed successfully.';
        static::assertStringContainsString($expected, $commandTester->getDisplay());
        static::assertSame(0, $commandTester->getStatusCode());

        $updates = $userRepo->updates;
        $updatedData = $updates[0][0];
        static::assertSame($userId, $updatedData['id']);
        static::assertSame($newPassword, $updatedData['password']);
    }

    public function testEmptyPasswordOption(): void
    {
        $userRepo = $this->createMock(EntityRepository::class);
        $command = new UserChangePasswordCommand($userRepo);

        $commandTester = new CommandTester($command);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage(':
    This value should not be blank. (code c1051bb4-d103-4f74-8988-acbcafc7fdc3)');

        $commandTester->setInputs(['', '', '']);
        $commandTester->execute([
            'username' => self::TEST_USERNAME,
        ]);
    }
}
