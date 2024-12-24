<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Util;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

#[Package('inventory')]
class VariantCombinationLoader
{
    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<array<string>>
     */
    public function load(string $productId, Context $context): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('LOWER(HEX(product.id))', 'product.option_ids as options', 'product.product_number as productNumber', 'product.states as productStates');
        $query->from('product');
        $query->where('product.parent_id = :id');
        $query->andWhere('product.version_id = :versionId');
        $query->setParameter('id', Uuid::fromHexToBytes($productId));
        $query->setParameter('versionId', Uuid::fromHexToBytes($context->getVersionId()));
        $query->andWhere('product.option_ids IS NOT NULL');

        $combinations = $query->executeQuery()->fetchAllAssociative();
        $combinations = FetchModeHelper::groupUnique($combinations);
        /** @var array<string, array{options: string, productNumber: string, productStates: string}> $combinations */
        foreach ($combinations as &$combination) {
            $combination['options'] = json_decode((string) $combination['options'], true, 512, \JSON_THROW_ON_ERROR);
        }

        return $combinations;
    }
}
