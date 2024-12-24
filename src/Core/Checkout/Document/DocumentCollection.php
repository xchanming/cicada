<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<DocumentEntity>
 */
#[Package('checkout')]
class DocumentCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'document_collection';
    }

    protected function getExpectedClass(): string
    {
        return DocumentEntity::class;
    }
}
