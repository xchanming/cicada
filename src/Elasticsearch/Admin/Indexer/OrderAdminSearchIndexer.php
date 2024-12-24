<?php declare(strict_types=1);

namespace Cicada\Elasticsearch\Admin\Indexer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IterableQuery;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;

#[Package('services-settings')]
final class OrderAdminSearchIndexer extends AbstractAdminIndexer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly IteratorFactory $factory,
        private readonly EntityRepository $repository,
        private readonly int $indexingBatchSize
    ) {
    }

    public function getDecorated(): AbstractAdminIndexer
    {
        throw new DecorationPatternException(self::class);
    }

    public function getEntity(): string
    {
        return OrderDefinition::ENTITY_NAME;
    }

    public function getName(): string
    {
        return 'order-listing';
    }

    public function getIterator(): IterableQuery
    {
        return $this->factory->createIterator($this->getEntity(), null, $this->indexingBatchSize);
    }

    public function globalData(array $result, Context $context): array
    {
        $ids = array_column($result['hits'], 'id');

        return [
            'total' => (int) $result['total'],
            'data' => $this->repository->search(new Criteria($ids), $context)->getEntities(),
        ];
    }

    public function fetch(array $ids): array
    {
        $data = $this->connection->fetchAllAssociative(
            '
            SELECT LOWER(HEX(order.id)) as id,
                   GROUP_CONCAT(DISTINCT tag.name SEPARATOR " ") as tags,
                   GROUP_CONCAT(DISTINCT country_translation.name SEPARATOR " ") as country,
                   GROUP_CONCAT(DISTINCT order_address.city SEPARATOR " ") as city,
                   GROUP_CONCAT(DISTINCT order_address.street SEPARATOR " ") as street,
                   GROUP_CONCAT(DISTINCT order_address.zipcode SEPARATOR " ") as zipcode,
                   GROUP_CONCAT(DISTINCT order_address.phone_number SEPARATOR " ") as phone_number,
                   GROUP_CONCAT(DISTINCT order_address.additional_address_line1 SEPARATOR " ") as additional_address_line1,
                   GROUP_CONCAT(DISTINCT order_address.additional_address_line2 SEPARATOR " ") as additional_address_line2,
                   GROUP_CONCAT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(document.config, "$.documentNumber")) SEPARATOR " ") as documentNumber,
                   order_customer.first_name,
                   order_customer.last_name,
                   order_customer.email,
                   order_customer.company,
                   order_customer.customer_number,
                   `order`.order_number,
                   `order`.amount_total,
                   order_delivery.tracking_codes
            FROM `order`
                LEFT JOIN order_customer
                    ON `order`.id = order_customer.order_id
                LEFT JOIN order_address
                    ON `order`.id = order_address.order_id
                LEFT JOIN country
                    ON order_address.country_id = country.id
                LEFT JOIN country_translation
                    ON country.id = country_translation.country_id
                LEFT JOIN order_tag
                    ON `order`.id = order_tag.order_id
                LEFT JOIN tag
                    ON order_tag.tag_id = tag.id
                LEFT JOIN order_delivery
                    ON `order`.id = order_delivery.order_id
                LEFT JOIN document
                    ON `order`.id = document.order_id
            WHERE order.id IN (:ids) AND `order`.version_id = :versionId
            GROUP BY order.id
        ',
            [
                'ids' => Uuid::fromHexToBytesList($ids),
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ],
            [
                'ids' => ArrayParameterType::BINARY,
            ]
        );

        $mapped = [];
        foreach ($data as $row) {
            $id = (string) $row['id'];
            $text = \implode(' ', array_filter(array_unique(array_values($row))));
            $mapped[$id] = ['id' => $id, 'text' => \strtolower($text)];
        }

        return $mapped;
    }
}
