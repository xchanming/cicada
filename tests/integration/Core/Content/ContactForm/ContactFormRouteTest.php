<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ContactForm;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\MailTemplateTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[Group('store-api')]
class ContactFormRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MailTemplateTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
    }

    #[DataProvider('contactFormWithDomainProvider')]
    public function testContactFormWithInvalid(string $name, \Closure $expectClosure): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/contact-form',
                [
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => $name,
                    'email' => 'test@xchanming.com',
                    'phone' => '12345/6789',
                    'subject' => 'Subject',
                    'comment' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $expectClosure($response);
    }

    public static function contactFormWithDomainProvider(): \Generator
    {
        yield 'subscribe with URL protocol HTTPS' => [
            'Y https://cicada.test',
            function (array $response): void {
                static::assertArrayHasKey('errors', $response);
                static::assertCount(1, $response['errors']);

                $errors = array_column(array_column($response['errors'], 'source'), 'pointer');

                static::assertContains('/name', $errors);
            },
        ];

        yield 'subscribe with URL protocol HTTP' => [
            'Y http://cicada.test',
            function (array $response): void {
                static::assertArrayHasKey('errors', $response);
                static::assertCount(1, $response['errors']);

                $errors = array_column(array_column($response['errors'], 'source'), 'pointer');

                static::assertContains('/name', $errors);
            },
        ];

        yield 'subscribe with URL localhost' => [
            'Y http://localhost:8080',
            function (array $response): void {
                static::assertArrayHasKey('errors', $response);
                static::assertCount(1, $response['errors']);

                $errors = array_column(array_column($response['errors'], 'source'), 'pointer');

                static::assertContains('/name', $errors);
            },
        ];
    }
}
