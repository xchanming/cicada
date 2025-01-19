<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Framework\Routing\Exception\InvalidRouteScopeException;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Event\StorefrontRenderEvent;
use Cicada\Storefront\Page\Navigation\NavigationPage;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class StorefrontRoutingTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;

    public function testForwardFromAddPromotionToHomePage(): void
    {
        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            StorefrontRenderEvent::class,
            function (StorefrontRenderEvent $event): void {
                $data = $event->getParameters();

                static::assertInstanceOf(NavigationPage::class, $data['page']);
                static::assertInstanceOf(CmsPageEntity::class, $data['page']->getCmsPage());
                static::assertSame('默认类目布局', $data['page']->getCmsPage()->getName());
            }
        );

        $response = $this->request(
            'POST',
            '/checkout/promotion/add',
            $this->tokenize('frontend.checkout.promotion.add', [
                'forwardTo' => 'frontend.home.page',
            ])
        );

        static::assertSame(200, $response->getStatusCode());
    }

    public function testForwardFromAddPromotionToApiFails(): void
    {
        $response = $this->request(
            'POST',
            '/checkout/promotion/add',
            $this->tokenize('frontend.checkout.promotion.add', [
                'forwardTo' => 'api.action.user.user-recovery.hash',
            ])
        );

        static::assertSame(Response::HTTP_PRECONDITION_FAILED, $response->getStatusCode());
        static::assertIsString($response->getContent());
        static::assertStringContainsString(InvalidRouteScopeException::class, $response->getContent());
    }
}
