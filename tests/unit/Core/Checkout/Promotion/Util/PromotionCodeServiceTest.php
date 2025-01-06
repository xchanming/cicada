<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Promotion\Util;

use Cicada\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeCollection;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeEntity;
use Cicada\Core\Checkout\Promotion\PromotionCollection;
use Cicada\Core\Checkout\Promotion\PromotionEntity;
use Cicada\Core\Checkout\Promotion\PromotionException;
use Cicada\Core\Checkout\Promotion\Util\PromotionCodeService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PromotionCodeService::class)]
class PromotionCodeServiceTest extends TestCase
{
    public function testAddIndividualCodesPromotionNotFound(): void
    {
        $context = Context::createDefaultContext();

        $promotionRepository = new StaticEntityRepository([new PromotionCollection([])]);

        $codeService = new PromotionCodeService(
            $promotionRepository,
            $this->createMock(EntityRepository::class),
            $this->createMock(Connection::class)
        );

        static::expectException(PromotionException::class);
        static::expectExceptionMessage('These promotions "promotionId" are not found');
        $codeService->addIndividualCodes('promotionId', 10, $context);
    }

    public function testAddIndividualCodesPromotionEmptyPattern(): void
    {
        $context = Context::createDefaultContext();

        $promotion = new PromotionEntity();
        $promotion->setId('promotionId');
        $promotion->setIndividualCodePattern('');

        $promotionRepository = new StaticEntityRepository([new PromotionCollection([$promotion])]);

        $codeService = new PromotionCodeService(
            $promotionRepository,
            $this->createMock(EntityRepository::class),
            $this->createMock(Connection::class)
        );

        static::expectException(PromotionException::class);
        static::expectExceptionMessage('The amount of possible codes is too low for the current pattern. Make sure your pattern is sufficiently complex.');
        $codeService->addIndividualCodes('promotionId', 10, $context);
    }

    public function testReplaceIndividualCodes(): void
    {
        $context = Context::createDefaultContext();

        $promotion = new PromotionEntity();
        $promotion->setId('promotionId');
        $promotion->setIndividualCodePattern('%s');

        $promotionRepository = new StaticEntityRepository([
            new PromotionCollection([$promotion]),
            [],
        ]);

        $promotionId = Uuid::randomHex();
        $individualCodeRepository = new StaticEntityRepository([new PromotionIndividualCodeCollection([])]);
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())->method('executeStatement')->with(
            'DELETE FROM promotion_individual_code WHERE promotion_id = :id',
            ['id' => Uuid::fromHexToBytes($promotionId)],
        );

        $codeService = new PromotionCodeService(
            $promotionRepository,
            $individualCodeRepository,
            $connection
        );

        $codeService->addIndividualCodes($promotionId, 10, $context);

        static::assertNotEmpty($individualCodeRepository->upserts[0]);
        static::assertCount(10, $individualCodeRepository->upserts[0]);
    }

    public function testAddIndividualCodes(): void
    {
        $context = Context::createDefaultContext();

        $promotion = new PromotionEntity();
        $promotion->setId('promotionId');
        $promotion->setIndividualCodePattern('%s');

        $code = new PromotionIndividualCodeEntity();
        $code->setId(Uuid::randomHex());
        $code->setCode('code');
        $codes = new PromotionIndividualCodeCollection([]);

        $promotion->setIndividualCodes($codes);

        $promotionRepository = new StaticEntityRepository([
            new PromotionCollection([$promotion]),
            [],
        ]);
        $individualCodeRepository = new StaticEntityRepository([new PromotionIndividualCodeCollection([])]);
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())->method('executeStatement');

        $codeService = new PromotionCodeService(
            $promotionRepository,
            $individualCodeRepository,
            $connection
        );

        $promotionId = Uuid::randomHex();

        $codeService->addIndividualCodes($promotionId, 10, $context);

        static::assertNotEmpty($individualCodeRepository->upserts[0]);
        static::assertCount(10, $individualCodeRepository->upserts[0]);
    }
}
