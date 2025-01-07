<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Service;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Category\Event\NavigationLoadedEvent;
use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Content\Category\SalesChannel\AbstractNavigationRoute;
use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Content\Category\Tree\TreeItem;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Util\AfterSort;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('discovery')]
class NavigationLoader implements NavigationLoaderInterface
{
    private readonly TreeItem $treeItem;

    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractNavigationRoute $navigationRoute
    ) {
        $this->treeItem = new TreeItem(null, []);
    }

    /**
     * {@inheritdoc}
     *
     * @throws CategoryNotFoundException
     */
    public function load(string $activeId, SalesChannelContext $context, string $rootId, int $depth = 2): Tree
    {
        $request = new Request();
        $request->query->set('buildTree', 'false');
        $request->query->set('depth', (string) $depth);

        $criteria = new Criteria();
        $criteria->setTitle('header::navigation');

        $categories = $this->navigationRoute
            ->load($activeId, $rootId, $request, $context, $criteria)
            ->getCategories();

        $navigation = $this->getTree($rootId, $categories, $categories->get($activeId));

        $event = new NavigationLoadedEvent($navigation, $context);

        $this->eventDispatcher->dispatch($event);

        return $event->getNavigation();
    }

    private function getTree(?string $rootId, CategoryCollection $categories, ?CategoryEntity $active): Tree
    {
        $parents = [];
        $items = [];
        foreach ($categories as $category) {
            $item = clone $this->treeItem;
            $item->setCategory($category);

            $parents[$category->getParentId()][$category->getId()] = $item;
            $items[$category->getId()] = $item;
        }

        foreach ($parents as $parentId => $children) {
            if (empty($parentId)) {
                continue;
            }

            $sorted = AfterSort::sort($children);

            $filtered = \array_filter($sorted, static fn (TreeItem $filter) => $filter->getCategory()->getActive() && $filter->getCategory()->getVisible());

            if (!isset($items[$parentId])) {
                continue;
            }

            $item = $items[$parentId];
            $item->setChildren($filtered);
        }

        $root = $parents[$rootId] ?? [];
        $root = AfterSort::sort($root);

        $filtered = [];
        /** @var TreeItem $item */
        foreach ($root as $key => $item) {
            if (!$item->getCategory()->getActive() || !$item->getCategory()->getVisible()) {
                continue;
            }

            $filtered[$key] = $item;
        }

        return new Tree($active, $filtered);
    }
}
