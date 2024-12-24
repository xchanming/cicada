<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Promotion\Integration\DataAbstractionLayer\Indexer;

use Cicada\Core\Checkout\Promotion\PromotionCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class PromotionExclusionIndexerTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<PromotionCollection>
     */
    private EntityRepository $promotionRepository;

    private Context $context;

    protected function setUp(): void
    {
        $this->promotionRepository = static::getContainer()->get('promotion.repository');
        $this->context = Context::createDefaultContext();
    }

    /**
     * tests that a update of promotion exclusions is written in excluded promotions too
     */
    #[Group('promotions')]
    public function testUpsertPromotionIndexerLogic(): void
    {
        $promotionA = $this->createPromotion([], 'Promotion A');
        $promotionB = $this->createPromotion([$promotionA], 'Promotion B');
        $promotionC = $this->createPromotion([$promotionA, $promotionB], 'Promotion C');

        $promotions = $this->promotionRepository->search(new Criteria([$promotionA, $promotionB, $promotionC]), $this->context)->getEntities();

        static::assertEquals([$promotionB, $promotionC], $promotions->get($promotionA)?->getExclusionIds(), 'Exclusion Promotion A has errors after creation');
        static::assertEquals([$promotionA, $promotionC], $promotions->get($promotionB)?->getExclusionIds(), 'Exclusion Promotion B has errors after creation');
        static::assertEquals([$promotionA, $promotionB], $promotions->get($promotionC)?->getExclusionIds(), 'Exclusion Promotion C has errors after creation');

        $this->promotionRepository->update([[
            'id' => $promotionC,
            'exclusionIds' => [],
        ]], $this->context);

        $promos = $this->promotionRepository->search(new Criteria(), $this->context)->getEntities();

        static::assertEquals([$promotionB], $promos->get($promotionA)?->getExclusionIds(), 'Exclusion Promotion A has errors after update');
        static::assertEquals([$promotionA], $promos->get($promotionB)?->getExclusionIds(), 'Exclusion Promotion B has errors after update');
        static::assertEquals([], $promos->get($promotionC)?->getExclusionIds(), 'Exclusion Promotion C has errors after update');
    }

    /**
     * tests that exclusions in all promotions are rewritten correctly after a promotion
     * has been deleted. No reference on the deleted entity may be in any exclusions of
     * other promotions
     */
    #[Group('promotions')]
    public function testDeletePromotionIndexerLogic(): void
    {
        $promotionA = $this->createPromotion([], 'Promotion A');
        $promotionB = $this->createPromotion([$promotionA], 'Promotion B');
        $promotionC = $this->createPromotion([$promotionA, $promotionB], 'Promotion C');

        $promotions = $this->promotionRepository->search(new Criteria([$promotionA, $promotionB, $promotionC]), $this->context)->getEntities();

        static::assertEquals([$promotionB, $promotionC], $promotions->get($promotionA)?->getExclusionIds(), 'Exclusion Promotion A has errors after creation');
        static::assertEquals([$promotionA, $promotionC], $promotions->get($promotionB)?->getExclusionIds(), 'Exclusion Promotion B has errors after creation');
        static::assertEquals([$promotionA, $promotionB], $promotions->get($promotionC)?->getExclusionIds(), 'Exclusion Promotion C has errors after creation');

        $this->promotionRepository->delete([[
            'id' => $promotionC,
        ]], $this->context);

        $promos = $this->promotionRepository->search(new Criteria(), $this->context)->getEntities();

        static::assertEquals([$promotionB], $promos->get($promotionA)?->getExclusionIds(), 'Exclusion Promotion A has errors after delete');
        static::assertEquals([$promotionA], $promos->get($promotionB)?->getExclusionIds(), 'Exclusion Promotion B has errors after delete');
    }

    /**
     * creates a promotion with exclusions and name
     *
     * @param array<string> $exclusions
     */
    private function createPromotion(array $exclusions, string $name): string
    {
        $id = Uuid::randomHex();

        $data = [
            'id' => $id,
            'name' => $name,
        ];

        if (\count($exclusions) > 0) {
            $data['exclusionIds'] = $exclusions;
        }

        $this->promotionRepository->upsert([$data], $this->context);

        return $id;
    }
}
