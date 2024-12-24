<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Validation\TranslationValidator;

/**
 * @internal
 */
#[CoversClass(TranslationValidator::class)]
class TranslationValidatorTest extends TestCase
{
    private TranslationValidator $translationValidator;

    protected function setUp(): void
    {
        $this->translationValidator = new TranslationValidator();
    }

    public function testValidate(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../Manifest/_fixtures/test/manifest.xml');

        $violations = $this->translationValidator->validate($manifest, null);
        static::assertCount(0, $violations->getElements());
    }

    public function testValidateReturnsErrorCollectionIfTranslationValidationsExists(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../Manifest/_fixtures/invalidTranslations/manifest.xml');

        $violations = $this->translationValidator->validate($manifest, null);
        static::assertSame('Missing translations for "Metadata":
- label: de-DE, fr-FR', $violations->first()?->getMessage());
    }
}
