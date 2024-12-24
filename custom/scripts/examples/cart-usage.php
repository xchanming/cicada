<?php

namespace Scripts\Examples;

use Doctrine\DBAL\Connection;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use  Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\TestDefaults;

require_once __DIR__ . '/base-script.php';

$env = 'prod'; // by default, kernel gets booted in dev

$kernel = require __DIR__ . '/../boot/boot.php';

class Main extends BaseScript
{
    public function run()
    {
        $ids = new IdsCollection();

        $this->getContainer()->get(Connection::class)
            ->executeStatement('DELETE FROM product WHERE product_number = :number', ['number' => 'p1']);

        $product = (new ProductBuilder($ids, 'p1'))
            ->price(100)
            ->visibility()
            ->build();

        $this->getContainer()->get('product.repository')
            ->create([$product], Context::createDefaultContext());

        // all services are public in script mode
        $service = $this->getContainer()->get(CartService::class);

        $context = $this->getContainer()->get(SalesChannelContextFactory::class)
            ->create($ids->get('token'), TestDefaults::SALES_CHANNEL);

        $cart = $service->getCart($ids->get('token'), $context);

        $item = $this->getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $ids->get('p1')], $context);

        $service->add($cart, [$item], $context);
    }
}


(new Main($kernel))->run();
