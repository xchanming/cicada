<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Aggregate\DocumentTypeTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<DocumentTypeTranslationEntity>
 */
#[Package('checkout')]
class DocumentTypeTranslationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'document_type_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return DocumentTypeTranslationEntity::class;
    }
}
