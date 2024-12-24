<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryDefinition;
use Symfony\Contracts\Service\ResetInterface;

#[Package('core')]
class CountrySerializer extends EntitySerializer implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cacheCountries = [];

    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $countryRepository)
    {
    }

    public function deserialize(Config $config, EntityDefinition $definition, $entity)
    {
        $deserialized = parent::deserialize($config, $definition, $entity);

        $deserialized = \is_array($deserialized) ? $deserialized : iterator_to_array($deserialized);

        if (!isset($deserialized['id']) && isset($deserialized['iso'])) {
            $id = $this->getCountryId($deserialized['iso']);

            if ($id) {
                $deserialized['id'] = $id;
            }
        }

        yield from $deserialized;
    }

    public function supports(string $entity): bool
    {
        return $entity === CountryDefinition::ENTITY_NAME;
    }

    public function reset(): void
    {
        $this->cacheCountries = [];
    }

    private function getCountryId(string $iso): ?string
    {
        if (\array_key_exists($iso, $this->cacheCountries)) {
            return $this->cacheCountries[$iso];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso));

        $this->cacheCountries[$iso] = $this->countryRepository->searchIds(
            $criteria,
            Context::createDefaultContext()
        )->firstId();

        return $this->cacheCountries[$iso];
    }
}
