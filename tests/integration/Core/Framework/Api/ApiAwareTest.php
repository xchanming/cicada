<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api;

use Cicada\Administration\Notification\NotificationDefinition;
use Cicada\Administration\Snippet\AppAdministrationSnippetDefinition;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Storefront\Theme\ThemeDefinition;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('skip-paratest')]
class ApiAwareTest extends TestCase
{
    use DataAbstractionLayerFieldTestBehaviour;
    use KernelTestBehaviour;

    public function testApiAware(): void
    {
        $cacheId = Hasher::hashFile(__DIR__ . '/fixtures/api-aware-fields.json');

        $kernel = KernelLifecycleManager::createKernel(
            null,
            true,
            $cacheId
        );
        $kernel->boot();
        $registry = $kernel->getContainer()->get(DefinitionInstanceRegistry::class);

        $mapping = [];

        foreach ($registry->getDefinitions() as $definition) {
            $entity = $definition->getEntityName();

            foreach ($definition->getFields() as $field) {
                $flag = $field->getFlag(ApiAware::class);
                if ($flag === null) {
                    continue;
                }

                if ($flag->isSourceAllowed(SalesChannelApiSource::class)) {
                    $mapping[] = $entity . '.' . $field->getPropertyName();
                }
            }
        }

        //        file_put_contents(__DIR__ . '/fixtures/api-aware-fields.json', json_encode($mapping, JSON_PRETTY_PRINT));

        // To update the mapping you can simply comment the following line and run the test once. The mapping will then be updated.
        // The line to update the mapping must of course be commented out again afterwards.
        $expected = file_get_contents(__DIR__ . '/fixtures/api-aware-fields.json');
        if (!\is_string($expected)) {
            static::fail(__DIR__ . '/fixtures/api-aware-fields.json could not be read');
        }
        $expected = \json_decode($expected, true, \JSON_THROW_ON_ERROR, \JSON_THROW_ON_ERROR);

        if (static::getContainer()->has(ThemeDefinition::class)) {
            $expected = array_merge(
                $expected,
                [
                    'theme.id',
                    'theme.technicalName',
                    'theme.name',
                    'theme.author',
                    'theme.description',
                    'theme.labels',
                    'theme.helpTexts',
                    'theme.customFields',
                    'theme.previewMediaId',
                    'theme.parentThemeId',
                    'theme.baseConfig',
                    'theme.configValues',
                    'theme.active',
                    'theme.media',
                    'theme.createdAt',
                    'theme.updatedAt',
                    'theme.translated',
                    'theme_translation.description',
                    'theme_translation.labels',
                    'theme_translation.helpTexts',
                    'theme_translation.customFields',
                    'theme_translation.createdAt',
                    'theme_translation.updatedAt',
                    'theme_translation.themeId',
                    'theme_translation.languageId',
                ]
            );
        }

        if (static::getContainer()->has(NotificationDefinition::class)) {
            $expected = array_merge(
                $expected,
                [
                    'notification.createdAt',
                    'notification.updatedAt',
                ]
            );
        }

        if (static::getContainer()->has(AppAdministrationSnippetDefinition::class)) {
            $expected = array_merge(
                $expected,
                [
                    'app_administration_snippet.value',
                    'app_administration_snippet.appId',
                    'app_administration_snippet.localeId',
                    'app_administration_snippet.createdAt',
                    'app_administration_snippet.updatedAt',
                ]
            );
        }

        $message = 'One or more fields have been changed in their visibility for the Store Api.
        This change must be carefully controlled to ensure that no sensitive data is given out via the Store API.';

        $diff = array_diff($mapping, $expected);
        static::assertEquals([], $diff, $message);

        $diff = array_diff($expected, $mapping);
        static::assertEquals([], $diff, $message);
    }
}
