<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\Captcha;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Routing\KernelListenerPriorities;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Storefront\Framework\Captcha\AbstractCaptcha;
use Cicada\Storefront\Framework\Captcha\BasicCaptcha;
use Cicada\Storefront\Framework\Captcha\CaptchaRouteListener;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class CaptchaRouteListenerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;
    use StorefrontControllerTestBehaviour;

    public function testGetSubscribedEventsReturnsCorrectEvents(): void
    {
        static::assertSame([
            KernelEvents::CONTROLLER => [
                ['validateCaptcha', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_SCOPE_VALIDATE],
            ],
        ], CaptchaRouteListener::getSubscribedEvents());
    }

    public function testThrowsExceptionWhenValidationFails(): void
    {
        $event = $this->getControllerEventMock();

        $this->expectExceptionMessage('The provided value for captcha');

        (new CaptchaRouteListener(
            $this->getCaptchas(true, false),
            static::getContainer()->get(SystemConfigService::class),
            static::getContainer()
        ))->validateCaptcha($event);
    }

    public function testJsonResponseWhenCaptchaValidationFails(): void
    {
        $systemConfig = static::getContainer()->get(SystemConfigService::class);

        $systemConfig->set('core.basicInformation.activeCaptchasV2', [
            BasicCaptcha::CAPTCHA_NAME => [
                'name' => BasicCaptcha::CAPTCHA_NAME,
                'isActive' => true,
            ],
        ]);

        /** @var EntityRepository<SalutationCollection> $repo */
        $repo = static::getContainer()->get('salutation.repository');
        $salutation = $repo->search(
            (new Criteria())->setLimit(1),
            Context::createDefaultContext()
        )->getEntities()->first();

        static::assertNotNull($salutation);

        $data = [
            'salutationId' => $salutation->getId(),
            'email' => 'kyln@xchanming.com',
            'firstName' => 'Ky',
            'lastName' => 'Le',
            'subject' => 'Captcha',
            'comment' => 'Basic Captcha',
            'phone' => '+4920 3920173',
            'cicada_basic_captcha_confirm' => 'notkyln',
        ];

        $browser = KernelLifecycleManager::createBrowser($this->getKernel());
        $browser->setServerParameter('HTTP_X-Requested-With', 'XMLHttpRequest');
        $browser->request(
            'POST',
            $_SERVER['APP_URL'] . '/form/contact',
            $this->tokenize('frontend.form.contact.send', $data)
        );

        $response = $browser->getResponse();
        static::assertInstanceOf(JsonResponse::class, $response);
        static::assertSame(200, $response->getStatusCode());

        $responseContent = $response->getContent() ?: '';
        $content = (array) json_decode($responseContent, null, 512, \JSON_THROW_ON_ERROR);

        static::assertCount(1, $content);

        /** @var \stdClass $var */
        $var = $content[0];

        $type = $var->type;

        static::assertSame('danger', $type);
    }

    public function testResponseWhenCaptchaValidationFails(): void
    {
        $systemConfig = static::getContainer()->get(SystemConfigService::class);

        $systemConfig->set('core.basicInformation.activeCaptchasV2', [
            BasicCaptcha::CAPTCHA_NAME => [
                'name' => BasicCaptcha::CAPTCHA_NAME,
                'isActive' => true,
            ],
        ]);

        $data = [
            'cicada_basic_captcha_confirm' => 'kyln',
        ];

        $browser = KernelLifecycleManager::createBrowser($this->getKernel());
        $browser->request(
            'POST',
            $_SERVER['APP_URL'] . '/account/register',
            $this->tokenize('frontend.account.register.save', $data)
        );

        $response = $browser->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent() ?: '');
    }

    /**
     * @return array<int, AbstractCaptcha|MockObject>
     */
    private function getCaptchas(bool $supports, bool $isValid): array
    {
        $captcha = $this->getMockBuilder(AbstractCaptcha::class)->getMock();

        $captcha->expects(static::once())
            ->method('supports')
            ->willReturn($supports);

        $captcha->expects($supports ? static::once() : static::never())
            ->method('isValid')
            ->willReturn($isValid);

        $captcha->expects($supports ? static::once() : static::never())
            ->method('shouldBreak')
            ->willReturn(true);

        return [$captcha];
    }

    private function getControllerEventMock(): ControllerEvent
    {
        return new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            function (): void {
            },
            self::getRequest($this->getRequestAttributes(true)),
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    private static function getRequest(ParameterBag $attributes): Request
    {
        return new Request([], [], $attributes->all(), [], [], []);
    }

    private function getRequestAttributes(bool $isCheckEnabled): ParameterBag
    {
        $param = [
            '_captcha' => $isCheckEnabled ? true : null,
        ];

        return new ParameterBag($isCheckEnabled ? $param : []);
    }
}
