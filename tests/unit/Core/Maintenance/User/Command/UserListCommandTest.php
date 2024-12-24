<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Maintenance\User\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\Acl\Role\AclRoleCollection;
use Cicada\Core\Framework\Api\Acl\Role\AclRoleEntity;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Maintenance\User\Command\UserListCommand;
use Cicada\Core\System\User\UserCollection;
use Cicada\Core\System\User\UserEntity;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversClass(UserListCommand::class)]
class UserListCommandTest extends TestCase
{
    public function testWithNoUsers(): void
    {
        /** @var StaticEntityRepository<UserCollection> $repo */
        $repo = new StaticEntityRepository(
            [
                new UserCollection(),
            ]
        );

        $command = new UserListCommand($repo);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();

        static::assertStringContainsString('There are no users', $output);
    }

    public function testWithUsers(): void
    {
        $ids = new IdsCollection();

        /** @var StaticEntityRepository<UserCollection> $repo */
        $repo = new StaticEntityRepository([
            new UserCollection([
                $this->createUser($ids->get('user1'), 'guy@cicada.com', 'guy', 'Guy', 'Marbello'),
                $this->createUser($ids->get('user2'), 'jen@cicada.com', 'jen', 'Jen', 'Dalimil', ['Moderator', 'CS']),
            ]),
        ]);

        $command = new UserListCommand($repo);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();

        static::assertStringContainsString('Guy Marbello', $output);
        static::assertStringContainsString('Jen Dalimil', $output);
    }

    public function testWithJson(): void
    {
        $ids = new IdsCollection();

        /** @var StaticEntityRepository<UserCollection> $repo */
        $repo = new StaticEntityRepository([
            new UserCollection([
                $this->createUser($ids->get('user1'), 'guy@cicada.com', 'guy', 'Guy', 'Marbello'),
                $this->createUser($ids->get('user2'), 'jen@cicada.com', 'jen', 'Jen', 'Dalimil', ['Moderator', 'CS']),
            ]),
        ]);

        $command = new UserListCommand($repo);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--json' => true]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();

        static::assertTrue(json_validate($output));
        static::assertStringContainsString('Guy Marbello', $output);
        static::assertStringContainsString('Jen Dalimil', $output);
    }

    /**
     * @param array<string> $roles
     */
    private function createUser(
        string $id,
        string $email,
        string $username,
        string $firstName,
        string $secondName,
        ?array $roles = null,
    ): UserEntity {
        $user = new UserEntity();
        $user->setId($id);
        $user->setEmail($email);
        $user->setActive(true);
        $user->setUsername($username);
        $user->setFirstName($firstName);
        $user->setLastName($secondName);
        $user->setAdmin($roles === null);
        $user->setCreatedAt(new \DateTime());

        if ($roles) {
            $user->setAclRoles(new AclRoleCollection(array_map(static function (string $role): AclRoleEntity {
                $aclRole = new AclRoleEntity();
                $aclRole->setId(Uuid::randomHex());
                $aclRole->setName($role);

                return $aclRole;
            }, $roles)));
        }

        return $user;
    }
}
