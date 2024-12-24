---
title: Prepare several methods to be native typed
issue: NEXT-14973
---
# Core
* Deprecated the following methods. They will all have a native typehint for their parameters and/or return type with Cicada 6.5.0.0
  * Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer::iterate
  * Cicada\Core\Checkout\Customer\DataAbstractionLayer\CustomerIndexer::iterate
  * Cicada\Core\Checkout\Promotion\DataAbstractionLayer\PromotionIndexer::iterate
  * Cicada\Core\Content\Category\DataAbstractionLayer\CategoryIndexer::iterate
  * Cicada\Core\Content\LandingPage\DataAbstractionLayer\LandingPageIndexer::iterate
  * Cicada\Core\Content\Media\DataAbstractionLayer\MediaFolderConfigurationIndexer::iterate
  * Cicada\Core\Content\Media\DataAbstractionLayer\MediaFolderIndexer::iterate
  * Cicada\Core\Content\Media\DataAbstractionLayer\MediaIndexer::iterate
  * Cicada\Core\Content\Product\DataAbstractionLayer\ProductIndexer::iterate
  * Cicada\Core\Content\Product\DataAbstractionLayer\ProductStreamUpdater::iterate
  * Cicada\Core\Content\ProductStream\DataAbstractionLayer\ProductStreamIndexer::iterate
  * Cicada\Core\Content\Rule\DataAbstractionLayer\RuleIndexer::iterate
  * Cicada\Core\System\SalesChannel\DataAbstractionLayer\SalesChannelIndexer::iterate

  * Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent::addData
  * Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent::addTemplateData

  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FieldSerializerInterface::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\BlobFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\BoolFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\CalculatedPriceFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\CartPriceFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\CashRoundingConfigFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ConfigJsonFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\CustomFieldsSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\DateFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\DateTimeFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\EmailFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FkFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FloatFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\IdFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\IntFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ListFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\LongTextFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ManyToManyAssociationFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ManyToOneAssociationFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\OneToManyAssociationFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\OneToOneAssociationFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\PasswordFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\PHPUnserializeFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\PriceDefinitionFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\PriceFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ReferenceVersionFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\RemoteAddressFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\StringFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\TranslatedFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\TranslationsAssociationFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\VersionDataPayloadFieldSerializer::decode
  * Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\VersionFieldSerializer::decode

  * Cicada\Core\Kernel::registerBundles
  * Cicada\Core\Kernel::getProjectDir
