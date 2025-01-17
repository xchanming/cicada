<?php declare(strict_types=1);

namespace Cicada\Tests\Bench\Storefront;

use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\PlatformRequest;
use Cicada\Storefront\Framework\Routing\RequestTransformer;
use Cicada\Storefront\Page\Search\SearchPageLoader;
use Cicada\Tests\Bench\AbstractBenchCase;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes as Bench;
use PhpBench\Attributes\BeforeMethods;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal - only for performance benchmarks
 */
class StorefrontSearchBench extends AbstractBenchCase
{
    public function setUp(): void
    {
        parent::setUp();

        $rulePayload = [];

        for ($i = 0; $i < 1500; ++$i) {
            $rulePayload[] = [
                'name' => 'test' . $i,
                'priority' => $i,
                'conditions' => [
                    [
                        'type' => 'andContainer',
                        'children' => [
                            [
                                'type' => 'alwaysValid',
                            ],
                        ],
                    ],
                ],
            ];
        }

        static::getContainer()->get('rule.repository')
            ->create($rulePayload, $this->context->getContext());

        // this will update the rule ids inside the context
        static::getContainer()->get(CartRuleLoader::class)->loadByToken($this->context, 'bench');
    }

    #[BeforeMethods(['setup'])]
    #[AfterMethods(['tearDown'])]
    #[Bench\Assert('mode(variant.time.avg) < 120ms +/- 10ms')]
    public function bench_searching_with_1500_active_rules(): void
    {
        $request = Request::create('/search?search=Simple', 'GET', ['search' => 'Simple']);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $this->context);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, 'localhost');

        static::getContainer()->get('request_stack')->push($request);

        static::getContainer()->get(SearchPageLoader::class)
            ->load($request, $this->context);
    }
}
