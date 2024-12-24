<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media;

use Cicada\Core\Content\Media\UnusedMediaPurger;
use Cicada\Core\Content\Test\Media\MediaFixtures;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(UnusedMediaPurger::class)]
class UnusedMediaPurgerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MediaFixtures;
    use QueueTestBehaviour;

    private const FIXTURE_FILE = __DIR__ . '/fixtures/cicada-logo.png';

    private UnusedMediaPurger $unusedMediaPurger;

    private EntityRepository $mediaRepo;

    private Context $context;

    protected function setUp(): void
    {
        $this->mediaRepo = static::getContainer()->get('media.repository');

        $this->context = Context::createDefaultContext();

        $this->unusedMediaPurger = new UnusedMediaPurger(
            $this->mediaRepo,
            $this->createMock(Connection::class),
            new EventDispatcher()
        );
    }

    public function testDeleteNotUsedMedia(): void
    {
        $this->setFixtureContext($this->context);

        $txt = $this->getTxt();
        $png = $this->getPng();
        $withProduct = $this->getMediaWithProduct();
        $withManufacturer = $this->getMediaWithManufacturer();

        $firstPath = $txt->getPath();
        $secondPath = $png->getPath();
        $thirdPath = $withProduct->getPath();
        $fourthPath = $withManufacturer->getPath();

        $this->getPublicFilesystem()->writeStream($firstPath, \fopen(self::FIXTURE_FILE, 'r'));
        $this->getPublicFilesystem()->writeStream($secondPath, \fopen(self::FIXTURE_FILE, 'r'));
        $this->getPublicFilesystem()->writeStream($thirdPath, \fopen(self::FIXTURE_FILE, 'r'));
        $this->getPublicFilesystem()->writeStream($fourthPath, \fopen(self::FIXTURE_FILE, 'r'));

        $this->unusedMediaPurger->deleteNotUsedMedia();
        $this->runWorker();

        $result = $this->mediaRepo->search(
            new Criteria([
                $txt->getId(),
                $png->getId(),
                $withProduct->getId(),
                $withManufacturer->getId(),
            ]),
            $this->context
        );

        static::assertNull($result->get($txt->getId()));
        static::assertNull($result->get($png->getId()));
        static::assertNotNull($result->get($withProduct->getId()));
        static::assertNotNull($result->get($withManufacturer->getId()));

        static::assertFalse($this->getPublicFilesystem()->has($firstPath));
        static::assertFalse($this->getPublicFilesystem()->has($secondPath));
        static::assertTrue($this->getPublicFilesystem()->has($thirdPath));
        static::assertTrue($this->getPublicFilesystem()->has($fourthPath));
    }
}
