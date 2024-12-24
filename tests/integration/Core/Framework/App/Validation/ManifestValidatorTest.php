<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Exception\AppValidationException;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Validation\ManifestValidator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @internal
 */
class ManifestValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;

    private ManifestValidator $manifestValidator;

    protected function setUp(): void
    {
        $this->manifestValidator = static::getContainer()->get(ManifestValidator::class);
    }

    public function testValidate(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../Manifest/_fixtures/test/manifest.xml');

        $this->manifestValidator->validate($manifest, Context::createDefaultContext());
    }

    #[DataProvider('invalidManifestProvider')]
    public function testValidateInvalidManifest(string $exceptionMessage): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../Manifest/_fixtures/invalidManifest/manifest.xml');

        $this->expectException(AppValidationException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->manifestValidator->validate($manifest, Context::createDefaultContext());
    }

    public static function invalidManifestProvider(): \Generator
    {
        yield ['The app "invalidManifestName" is invalid'];
        yield ['Missing translations for "Metadata":'];
        yield ['The technical app name "invalidManifestName" in the "manifest.xml" and the folder name must be equal.'];
        yield ['The following custom components are not allowed to be used in app configuration:'];
        yield ['The following webhooks are not hookable:'];
        yield ['The following permissions are missing:'];
    }
}
