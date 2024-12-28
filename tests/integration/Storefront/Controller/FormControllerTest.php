<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Storefront\Controller\FormController;
use Cicada\Storefront\Framework\Routing\RequestTransformer;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('buyers-experience')]
class FormControllerTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;
    use StorefrontControllerTestBehaviour;

    public function testHandleNewsletter(): void
    {
        $data = [
            'option' => 'subscribe',
            'email' => 'test@example.com',
            'name' => 'John',
        ];

        $response = $this->request(
            'POST',
            '/form/newsletter',
            $this->tokenize('frontend.form.newsletter.register.handle', $data)
        );

        static::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $content = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $type = $content[0]['type'];

        static::assertInstanceOf(JsonResponse::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertCount(2, $content);
        static::assertSame('success', $type);
    }

    public function testHandleNewsletterFails(): void
    {
        // with incorrect email
        $data = [
            'option' => 'unsubscribe',
            'email' => 'test@example',
        ];

        $response = $this->request(
            'POST',
            '/form/newsletter',
            $this->tokenize('frontend.form.newsletter.register.handle', $data)
        );
        $responseContent = $response->getContent();
        $content = json_decode((string) $responseContent, false, 512, \JSON_THROW_ON_ERROR);

        static::assertInstanceOf(JsonResponse::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertEmpty($content);
    }

    public function testHandleNewsletterUsesProperSalesChannelUrl(): void
    {
        $formController = static::getContainer()->get(FormController::class);

        $request = new Request();
        $request->attributes->set('sw-sales-channel-absolute-base-url', 'wrong.test');
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, 'correct.test');

        $requestDataBag = new RequestDataBag();
        $requestDataBag->set('option', FormController::SUBSCRIBE);

        $formController->handleNewsletter($request, $requestDataBag, $this->createSalesChannelContext());

        static::assertSame('correct.test', $requestDataBag->get('storefrontUrl'));
    }

    public function testSendContactForm(): void
    {
        /** @var EntityRepository<SalutationCollection> $salutation */
        $salutation = static::getContainer()->get('salutation.repository');

        $salutation = $salutation->search(
            (new Criteria())->setLimit(1),
            Context::createDefaultContext()
        )->getEntities()->first();
        static::assertNotNull($salutation);

        $data = [
            'salutationId' => $salutation->getId(),
            'email' => 'test@example.com',
            'name' => 'John',
            'subject' => 'Lorem ipsum',
            'comment' => 'Lorem ipsum dolor',
            'phone' => '+4920 3920173',
        ];

        $request = new Request();
        $request->setSession($this->getSession());
        static::getContainer()->get('request_stack')->push($request);

        $token = $this->tokenize('frontend.form.contact.send', $data);
        static::getContainer()->get('request_stack')->pop();

        $response = $this->request(
            'POST',
            '/form/contact',
            $token
        );

        $responseContent = $response->getContent();
        $content = json_decode((string) $responseContent, true, 512, \JSON_THROW_ON_ERROR);
        $type = $content[0]['type'];

        static::assertInstanceOf(JsonResponse::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertCount(1, $content);
        static::assertSame('success', $type);
    }

    public function testSendContactFormFails(): void
    {
        // without salutationId and with incorrect email
        $data = [
            'email' => 'test@example',
            'name' => 'John',
            'subject' => 'Lorem ipsum',
            'comment' => 'Lorem ipsum dolor',
            'phone' => '+4920 3920173',
        ];

        $response = $this->request(
            'POST',
            '/form/contact',
            $this->tokenize('frontend.form.contact.send', $data)
        );

        $responseContent = $response->getContent();
        $content = (array) json_decode((string) $responseContent, true, 512, \JSON_THROW_ON_ERROR);
        $type = $content[0]['type'];
        $messageCount = mb_substr_count((string) $content[0]['alert'], '<li>');

        static::assertInstanceOf(JsonResponse::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertCount(1, $content);
        static::assertSame('danger', $type);
        static::assertSame(2, $messageCount);
    }
}
