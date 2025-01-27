<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldAccessorBuilder;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('framework')]
class JsonFieldAccessorBuilder implements FieldAccessorBuilderInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    public function buildAccessor(string $root, Field $field, Context $context, string $accessor): ?string
    {
        if (!$field instanceof JsonField) {
            return null;
        }

        $jsonPath = preg_replace(
            '#^' . preg_quote($field->getPropertyName(), '#') . '#',
            '',
            $accessor
        );

        if (empty($jsonPath)) {
            return EntityDefinitionQueryHelper::escape($root) . '.' . EntityDefinitionQueryHelper::escape($field->getStorageName());
        }

        // enquote hyphenated json keys in path
        if (str_contains($jsonPath, '-')) {
            $jsonPathParts = explode('.', $jsonPath);
            foreach ($jsonPathParts as $index => $jsonPathPart) {
                if ($index === 0) {
                    continue;
                }
                if (str_contains($jsonPathPart, '-')) {
                    $jsonPathParts[$index] = \sprintf('"%s"', $jsonPathPart);
                }
            }
            $jsonPath = implode('.', $jsonPathParts);
        }

        $jsonValueExpr = \sprintf(
            'JSON_EXTRACT(%s.%s, %s)',
            EntityDefinitionQueryHelper::escape($root),
            EntityDefinitionQueryHelper::escape($field->getStorageName()),
            (string) $this->connection->quote('$' . $jsonPath)
        );

        $embeddedField = $this->getField($jsonPath, $field->getPropertyMapping());
        $accessor = $this->getFieldAccessor($jsonValueExpr, $embeddedField);

        /*
         * Values extracted from json have distinct json types, that are different from normal value types.
         * We need to convert json nulls into sql nulls.
         *
         * For example: `JSON_EXTRACT('{"foo":null}', '$.foo') IS NOT NULL`
         */
        return \sprintf('IF(JSON_TYPE(%s) != "NULL", %s, NULL)', $jsonValueExpr, $accessor);
    }

    /**
     * @param Field[] $fields
     */
    private function getField(string $path, array $fields): ?Field
    {
        /** @var string $fieldName */
        $fieldName = preg_replace(
            '#^\.("([^"]*)"|([^.]*)).*#',
            '$2$3',
            $path
        );
        $subPath = mb_substr($path, mb_strlen($fieldName) + 1);

        foreach ($fields as $field) {
            if ($field->getPropertyName() !== $fieldName) {
                continue;
            }

            if ($field instanceof JsonField && !empty($field->getPropertyMapping())) {
                return $this->getField($subPath, $field->getPropertyMapping());
            }

            return $field;
        }

        return null;
    }

    private function getFieldAccessor(string $jsonValueExpr, ?Field $field = null): string
    {
        if ($field instanceof IntField || $field instanceof FloatField) {
            return \sprintf('JSON_UNQUOTE(%s) + 0.0', $jsonValueExpr);
        }

        if ($field instanceof BoolField) {
            return \sprintf(
                'IF(JSON_UNQUOTE(%s) != "true" && JSON_UNQUOTE(%s) = 0, 0, 1)',
                $jsonValueExpr,
                $jsonValueExpr
            );
        }

        if ($field instanceof DateTimeField) {
            return \sprintf('CAST(JSON_UNQUOTE(%s) AS datetime(3))', $jsonValueExpr);
        }

        if ($field instanceof DateField) {
            return \sprintf('CAST(JSON_UNQUOTE(%s) AS DATE)', $jsonValueExpr);
        }

        // The CONVERT is required for mariadb support (mysqls JSON_UNQUOTE returns utf8mb4)
        return \sprintf('CONVERT(JSON_UNQUOTE(%s) USING "utf8mb4") COLLATE utf8mb4_unicode_ci', $jsonValueExpr);
    }
}
