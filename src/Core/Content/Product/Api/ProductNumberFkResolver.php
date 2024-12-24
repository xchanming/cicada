<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Api;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Sync\AbstractFkResolver;
use Cicada\Core\Framework\Api\Sync\FkReference;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('core')]
class ProductNumberFkResolver extends AbstractFkResolver
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public static function getName(): string
    {
        return 'product.number';
    }

    /**
     * @param array<FkReference> $map
     *
     * @return array<FkReference>
     */
    public function resolve(array $map): array
    {
        $numbers = \array_map(fn ($id) => $id->value, $map);

        $numbers = \array_filter(\array_unique($numbers));

        if (empty($numbers)) {
            return $map;
        }

        $hash = $this->connection->fetchAllKeyValue(
            'SELECT product_number, LOWER(HEX(id)) FROM product WHERE product_number IN (:numbers) AND version_id = :version',
            ['numbers' => $numbers, 'version' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION)],
            ['numbers' => ArrayParameterType::STRING]
        );

        foreach ($map as $reference) {
            if (isset($hash[$reference->value])) {
                $reference->resolved = $hash[$reference->value];
            }
        }

        return $map;
    }
}
