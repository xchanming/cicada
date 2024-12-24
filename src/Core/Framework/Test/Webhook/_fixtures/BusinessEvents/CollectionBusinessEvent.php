<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EntityCollectionType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\System\Tax\TaxCollection;
use Cicada\Core\System\Tax\TaxDefinition;

/**
 * @internal
 */
class CollectionBusinessEvent implements FlowEventAware, BusinessEventEncoderTestInterface
{
    public function __construct(private readonly TaxCollection $taxes)
    {
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('taxes', new EntityCollectionType(TaxDefinition::class));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function getEncodeValues(string $cicadaVersion): array
    {
        $taxes = [];

        foreach ($this->taxes->getElements() as $tax) {
            $taxes[] = [
                'id' => $tax->getId(),
                '_uniqueIdentifier' => $tax->getId(),
                'versionId' => null,
                'name' => $tax->getName(),
                'taxRate' => $tax->getTaxRate(),
                'position' => $tax->getPosition(),
                'customFields' => null,
                'translated' => [],
                'createdAt' => $tax->getCreatedAt() ? $tax->getCreatedAt()->format(\DATE_RFC3339_EXTENDED) : null,
                'updatedAt' => null,
                'extensions' => [],
                'apiAlias' => 'tax',
            ];
        }

        return [
            'taxes' => $taxes,
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

    public function getTaxes(): TaxCollection
    {
        return $this->taxes;
    }
}
