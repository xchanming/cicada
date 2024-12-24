<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\Language\LanguageEntity;
use Symfony\Contracts\Service\ResetInterface;

#[Package('core')]
class LanguageSerializer extends EntitySerializer implements ResetInterface
{
    /**
     * @var array<string, array{id: string, locale: array{id: string}}|null>
     */
    private array $cacheLanguages = [];

    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $languageRepository)
    {
    }

    public function deserialize(Config $config, EntityDefinition $definition, $entity)
    {
        $deserialized = parent::deserialize($config, $definition, $entity);

        $deserialized = \is_array($deserialized) ? $deserialized : iterator_to_array($deserialized);

        if (!isset($deserialized['id']) && isset($deserialized['locale']['code'])) {
            $language = $this->getLanguageSerialized($deserialized['locale']['code']);

            // if we dont find it by name, only set the id to the fallback if we dont have any other data
            if (!$language && \count($deserialized) === 1) {
                $deserialized['id'] = Defaults::LANGUAGE_SYSTEM;
                unset($deserialized['locale']);
            }

            if ($language) {
                $deserialized = array_merge_recursive($deserialized, $language);
            }
        }

        yield from $deserialized;
    }

    public function supports(string $entity): bool
    {
        return $entity === LanguageDefinition::ENTITY_NAME;
    }

    public function reset(): void
    {
        $this->cacheLanguages = [];
    }

    /**
     * @return array{id: string, locale: array{id: string}}|null
     */
    private function getLanguageSerialized(string $code): ?array
    {
        if (\array_key_exists($code, $this->cacheLanguages)) {
            return $this->cacheLanguages[$code];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('locale.code', $code));
        $criteria->addAssociation('locale');
        $language = $this->languageRepository->search($criteria, Context::createDefaultContext())->first();

        $this->cacheLanguages[$code] = null;
        if ($language instanceof LanguageEntity && $language->getLocale() !== null) {
            $this->cacheLanguages[$code] = [
                'id' => $language->getId(),
                'locale' => ['id' => $language->getLocale()->getId()],
            ];
        }

        return $this->cacheLanguages[$code];
    }
}
