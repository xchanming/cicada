---
title: Add generic template to EntityRepository
issue: NEXT-22942
author: Michael Telgmann
author_github: mitelg
---
# Core
* Added a generic type template to the `EntityRepository` class. This allows to define the entity type of the repository, which improves the IDE support and static code analysis.
___
# Upgrade Information
## Generic type template for EntityRepository
The `EntityRepository` class now has a generic type template.
This allows to define the entity type of the repository, which improves the IDE support and static code analysis.
Usage:

```php
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MyService
    /**
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(private readonly EntityRepository $productRepository)
    {}

    public function doSomething(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));

        $products = $this->productRepository->search($criteria, $context)->getEntities();
        // $products is now inferred as ProductCollection
    }
```
