<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules;

use Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules\DALFieldsMustBeRegisteredWithSchemaBuilder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * @internal
 *
 * @extends  RuleTestCase<DALFieldsMustBeRegisteredWithSchemaBuilder>
 */
#[CoversClass(DALFieldsMustBeRegisteredWithSchemaBuilder::class)]
class DALFieldsMustBeRegisteredWithSchemaBuilderTest extends RuleTestCase
{
    #[RunInSeparateProcess]
    public function testRule(): void
    {
        // not in namespace, autoload is passed as true, error
        $this->analyse([__DIR__ . '/data/DALFieldsMustBeRegisteredWithSchemaBuilder/NotRegisteredField.php'], [
            [
                'Field Cicada\Core\Framework\DataAbstractionLayer\Field\Field\NotRegisteredField must be registered with Cicada\Core\Framework\DataAbstractionLayer\Dbal\SchemaBuilder',
                12,
            ],
        ]);

        // not in core, ignore
        $this->analyse([__DIR__ . '/data/DALFieldsMustBeRegisteredWithSchemaBuilder/NotInCoreNamespace.php'], []);

        // if we are in a Test namespace, we ignore
        $this->analyse([__DIR__ . '/data/DALFieldsMustBeRegisteredWithSchemaBuilder/InTestNamespace.php'], []);

        // ignored fields
        $this->analyse([__DIR__ . '/data/DALFieldsMustBeRegisteredWithSchemaBuilder/MyAssociationField.php'], []);
        $this->analyse([__DIR__ . '/data/DALFieldsMustBeRegisteredWithSchemaBuilder/MyTranslatedField.php'], []);
    }

    /**
     * @return DALFieldsMustBeRegisteredWithSchemaBuilder
     */
    protected function getRule(): Rule
    {
        return new DALFieldsMustBeRegisteredWithSchemaBuilder($this->createReflectionProvider());
    }
}
