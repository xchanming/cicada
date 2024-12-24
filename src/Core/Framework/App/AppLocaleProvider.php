<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Locale\LanguageLocaleCodeProvider;
use Cicada\Core\System\User\UserCollection;
use Cicada\Core\System\User\UserDefinition;

#[Package('core')]
class AppLocaleProvider
{
    /**
     * @internal
     *
     * @param EntityRepository<UserCollection> $userRepository
     */
    public function __construct(
        private readonly EntityRepository $userRepository,
        private readonly LanguageLocaleCodeProvider $languageLocaleProvider
    ) {
    }

    public function getLocaleFromContext(Context $context): string
    {
        if (!$context->getSource() instanceof AdminApiSource) {
            return $this->languageLocaleProvider->getLocaleForLanguageId($context->getLanguageId());
        }

        $source = $context->getSource();

        if ($source->getUserId() === null) {
            return $this->languageLocaleProvider->getLocaleForLanguageId($context->getLanguageId());
        }

        $criteria = new Criteria([$source->getUserId()]);
        $criteria->addAssociation('locale');

        $user = $this->userRepository->search($criteria, $context)->getEntities()->first();

        if ($user === null) {
            throw new EntityNotFoundException(UserDefinition::ENTITY_NAME, $source->getUserId());
        }

        $locale = $user->getLocale();
        \assert($locale !== null);

        return $locale->getCode();
    }
}
