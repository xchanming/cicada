<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\CmsBlock;

use Cicada\Core\Framework\App\Aggregate\CmsBlockTranslation\AppCmsBlockTranslationCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Cms\Xml\Block;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @phpstan-import-type BlockArray from Block
 */
#[Package('buyers-experience')]
class AppCmsBlockEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $appId;

    /**
     * @var AppEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $app;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $name;

    /**
     * @var BlockArray
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $block;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $template;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $styles;

    /**
     * @var AppCmsBlockTranslationCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $translations;

    protected ?string $label = null;

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function getApp(): AppEntity
    {
        return $this->app;
    }

    public function setApp(AppEntity $app): void
    {
        $this->app = $app;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return BlockArray
     */
    public function getBlock(): array
    {
        return $this->block;
    }

    /**
     * @param BlockArray $block
     */
    public function setBlock(array $block): void
    {
        $this->block = $block;
    }

    public function getTranslations(): AppCmsBlockTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(AppCmsBlockTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function getStyles(): string
    {
        return $this->styles;
    }

    public function setStyles(string $styles): void
    {
        $this->styles = $styles;
    }
}
