<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Maintenance\User\Command;

use Cicada\Core\Framework\Api\Acl\Role\AclRoleCollection;
use Cicada\Core\Framework\Api\Acl\Role\AclRoleEntity;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Maintenance\User\Command\UserListCommand;
use Cicada\Core\System\User\UserCollection;
use Cicada\Core\System\User\UserEntity;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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
                $this->createUser($ids->get('user1'), 'guy@xchanming.com', 'guy', 'Guy'),
                $this->createUser($ids->get('user2'), 'jen@xchanming.com', 'jen', 'Jen', ['Moderator', 'CS']),
            ]),
        ]);

        $command = new UserListCommand($repo);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();

        static::assertStringContainsString('Guy', $output);
        static::assertStringContainsString('Jen', $output);
    }

    public function testWithJson(): void
    {
        $ids = new IdsCollection();

        /** @var StaticEntityRepository<UserCollection> $repo */
        $repo = new StaticEntityRepository([
            new UserCollection([
                $this->createUser($ids->get('user1'), 'guy@xchanming.com', 'guy', 'Guy'),
                $this->createUser($ids->get('user2'), 'jen@xchanming.com', 'jen', 'Jen', ['Moderator', 'CS']),
            ]),
        ]);

        $command = new UserListCommand($repo);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--json' => true]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();

        static::assertTrue(json_validate($output));
        static::assertStringContainsString('Guy', $output);
        static::assertStringContainsString('Jen', $output);
    }

    /**
     * @param array<string> $roles
     */
    private function createUser(
        string $id,
        string $email,
        string $username,
        string $name,
        ?array $roles = null,
    ): UserEntity {
        $user = new UserEntity();
        $user->setId($id);
        $user->setEmail($email);
        $user->setActive(true);
        $user->setUsername($username);
        $user->setName($name);
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
