<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\System\Tax\TaxDefinition;
use Cicada\Core\System\Tax\TaxEntity;

/**
 * @internal
 */
class EntityBusinessEvent implements FlowEventAware, BusinessEventEncoderTestInterface
{
    public function __construct(private readonly TaxEntity $tax)
    {
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('tax', new EntityType(TaxDefinition::class));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getEncodeValues(string $cicadaVersion): array
    {
        return [
            'tax' => [
                'id' => $this->tax->getId(),
                '_uniqueIdentifier' => $this->tax->getId(),
                'versionId' => null,
                'name' => $this->tax->getName(),
                'taxRate' => $this->tax->getTaxRate(),
                'position' => $this->tax->getPosition(),
                'customFields' => null,
                'translated' => [],
                'createdAt' => $this->tax->getCreatedAt() ? $this->tax->getCreatedAt()->format(\DATE_RFC3339_EXTENDED) : null,
                'updatedAt' => null,
                'extensions' => [],
                'apiAlias' => 'tax',
            ],
        ];
    }

    public function getName(): string
    {
        return 'test';
    }

    public function getContext(): Context
    {
        return Context::createDefaultContext();
    }

    public function getTax(): TaxEntity
    {
        return $this->tax;
    }
}
