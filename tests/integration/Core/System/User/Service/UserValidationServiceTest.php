<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\User\Service;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\User\Service\UserValidationService;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('services-settings')]
class UserValidationServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $userRepository;

    private EntityRepository $localeRepository;

    private UserValidationService $userValidationService;

    protected function setUp(): void
    {
        $this->userRepository = static::getContainer()->get('user.repository');
        $this->localeRepository = static::getContainer()->get('locale.repository');
        $this->userValidationService = static::getContainer()->get(UserValidationService::class);
    }

    public function testIfReturnsTrueForUniqueEmails(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();
        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@cicada.com',
                'password' => TestDefaults::HASHED_PASSWORD,
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertTrue($this->userValidationService->checkEmailUnique('some@other.email', $userIdToTest, $context));
        static::assertTrue($this->userValidationService->checkEmailUnique('user@cicada.com', $userId, $context));
    }

    public function testIfReturnsFalseForDuplicateEmails(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();

        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@cicada.com',
                'password' => TestDefaults::HASHED_PASSWORD,
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertFalse($this->userValidationService->checkEmailUnique('user@cicada.com', $userIdToTest, $context));
    }

    public function testIfReturnsTrueForUniqueUsernames(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();
        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@cicada.com',
                'password' => TestDefaults::HASHED_PASSWORD,
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertTrue($this->userValidationService->checkUsernameUnique('other User', $userIdToTest, $context));
        static::assertTrue($this->userValidationService->checkUsernameUnique('some User', $userId, $context));
    }

    public function testIfReturnsFalseForDuplicateUsernames(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();
        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@cicada.com',
                'password' => TestDefaults::HASHED_PASSWORD,
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertFalse($this->userValidationService->checkUsernameUnique('some User', $userIdToTest, $context));
    }
}
