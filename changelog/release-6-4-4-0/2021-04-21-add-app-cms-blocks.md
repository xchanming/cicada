---
title: Allow app developers to extend the CMS by adding custom blocks  
issue: NEXT-14408
---
# Core
* Added new entity `app_cms_block` in `Cicada\Core\Framework\App\Aggregate\CmsBlock\AppCmsBlockEntity`
* Added new tables
    * `app_cms_block`
    * `app_cms_block_translation`
* Added new classes
    * `Cicada\Core\Framework\App\Aggregate\CmsBlock\AppCmsBlockCollection`
    * `Cicada\Core\Framework\App\Aggregate\CmsBlock\AppCmsBlockDefinition`
    * `Cicada\Core\Framework\App\Aggregate\CmsBlock\AppCmsBlockEntity`
    * `Cicada\Core\Framework\App\Aggregate\CmsBlockTranslation\AppCmsBlockTranslationCollection`
    * `Cicada\Core\Framework\App\Aggregate\CmsBlockTranslation\AppCmsBlockTranslationDefinition`
    * `Cicada\Core\Framework\App\Aggregate\CmsBlockTranslation\AppCmsBlockTranslationEntity`
    * `Cicada\Core\Framework\App\Api\AppCmsController`
    * `Cicada\Core\Framework\App\Cms\Xml\Block`
    * `Cicada\Core\Framework\App\Cms\Xml\Blocks`
    * `Cicada\Core\Framework\App\Cms\Xml\Config`
    * `Cicada\Core\Framework\App\Cms\Xml\DefaultConfig`
    * `Cicada\Core\Framework\App\Cms\Xml\Slot`
    * `Cicada\Core\Framework\App\Cms\AbstractBlockTemplateLoader`
    * `Cicada\Core\Framework\App\Cms\BlockTemplateLoader`
    * `Cicada\Core\Framework\App\Cms\CmsExtensions`
    * `Cicada\Core\Framework\App\Exception\AppCmsExtensionException`
    * `Cicada\Core\Framework\App\Lifecycle\Persister\CmsBlockPersister`
* Added abstract method `Cicada\Core\Framework\App\Lifecycle\AbstractAppLoader::getCmsExtensions`
* Added method `Cicada\Core\Framework\App\Lifecycle\AppLoader::getCmsExtensions`
* Updated private method to `Cicada\Core\Framework\App\Lifecycle\AppLifeCycle::updateApp` to persist CMS blocks provided by app
* Added new XML schema definition `cms-1.0.xsd`
* Updated `Cicada\Core\Framework\App\AppDefinition::defineFields` with new one-to-many association towards `AppCmsBlockDefinition` 
* Added new property `Cicada\Core\Framework\App\AppEntity::$cmsBlocks`
___
# API
* Added route `/api/app-system/cms/blocks` to retrieve custom CMS blocks provided by **activated** apps
___
# Upgrade Information
Existing apps **DO NOT** break with the introduced changes as they are backwards compatible.

If you implement `Cicada\Core\Framework\App\Lifecycle\AbstractAppLoader` make sure to add the new method `::getCmsExtensions`.
