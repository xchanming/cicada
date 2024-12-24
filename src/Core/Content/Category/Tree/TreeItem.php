<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Tree;

use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Category\CategoryException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('inventory')]
class TreeItem extends Struct
{
    /**
     * @internal public to allow AfterSort::sort()
     */
    public ?string $afterId;

    /**
     * @var CategoryEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $category;

    /**
     * @var TreeItem[]
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $children;

    /**
     * @param TreeItem[] $children
     */
    public function __construct(
        ?CategoryEntity $category,
        array $children
    ) {
        $this->category = $category;
        $this->children = $children;
        $this->afterId = $category ? $category->getAfterCategoryId() : null;
    }

    public function getId(): string
    {
        return $this->getCategory()->getId();
    }

    public function setCategory(CategoryEntity $category): void
    {
        $this->category = $category;
        $this->afterId = $category->getAfterCategoryId();
    }

    public function getCategory(): CategoryEntity
    {
        if (!$this->category) {
            throw CategoryException::categoryNotFound('treeItem');
        }

        return $this->category;
    }

    /**
     * @return TreeItem[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChildren(TreeItem ...$items): void
    {
        foreach ($items as $item) {
            $this->children[] = $item;
        }
    }

    /**
     * @param TreeItem[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function getApiAlias(): string
    {
        return 'category_tree_item';
    }
}
