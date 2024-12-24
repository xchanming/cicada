<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Response;

use Cicada\Core\Framework\Api\Context\ContextSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
interface ResponseFactoryInterface
{
    public function supports(string $contentType, ContextSource $origin): bool;

    public function createDetailResponse(
        Criteria $criteria,
        Entity $entity,
        EntityDefinition $definition,
        Request $request,
        Context $context,
        bool $setLocationHeader = false
    ): Response;

    /**
     * @template TEntityCollection of EntityCollection
     *
     * @param EntitySearchResult<covariant TEntityCollection> $searchResult
     */
    public function createListingResponse(
        Criteria $criteria,
        EntitySearchResult $searchResult,
        EntityDefinition $definition,
        Request $request,
        Context $context
    ): Response;

    public function createRedirectResponse(
        EntityDefinition $definition,
        string $id,
        Request $request,
        Context $context
    ): Response;
}
