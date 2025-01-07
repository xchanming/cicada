<?php declare(strict_types=1);

namespace Cicada\Core\System\Language\SalesChannel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('fundamentals@discovery')]
class LanguageRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<LanguageCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<LanguageCollection> $languages
     */
    public function __construct(EntitySearchResult $languages)
    {
        parent::__construct($languages);
    }

    public function getLanguages(): LanguageCollection
    {
        return $this->object->getEntities();
    }
}
