<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PropertyType;

/**
 * Resolves config/property_schema.php into the field set a given property type
 * accepts, and coerces submitted values to their declared kind. Shared by the
 * store/update requests so validation and storage agree.
 */
class PropertySpecs
{
    /**
     * Every field a type may submit, keyed by field key, including the
     * purpose-gated groups (payment plan, rental terms).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fieldsForType(?int $propertyTypeId): array
    {
        $schema = config('property_schema');
        $slug = $propertyTypeId
            ? PropertyType::whereKey($propertyTypeId)->value('slug')
            : null;

        $definition = $schema['types'][$slug] ?? null;

        if ($definition === null) {
            return [];
        }

        // Groups not listed on any type still apply: 'always' groups show for
        // every type, and purpose-gated ones (payment plan, rental terms)
        // depend on the chosen purpose rather than the type. Accept both here
        // and let the form decide what to render.
        $groupNames = array_merge(
            $definition['groups'],
            array_keys(array_filter(
                $schema['groups'],
                fn (array $group) => isset($group['purpose']) || ($group['always'] ?? false),
            )),
        );

        $fields = [];

        foreach ($groupNames as $groupName) {
            foreach ($schema['groups'][$groupName]['fields'] ?? [] as $field) {
                $fields[$field['key']] = $field;
            }
        }

        return $fields;
    }

    /**
     * Drop unknown keys and cast each value to the kind the schema declares,
     * so a checkbox arriving as "on" or "1" lands in JSONB as a real boolean.
     *
     * @param  array<string, mixed>  $submitted
     * @return array<string, mixed>
     */
    public static function coerce(array $submitted, ?int $propertyTypeId): array
    {
        $fields = self::fieldsForType($propertyTypeId);
        $clean = [];

        foreach ($submitted as $key => $value) {
            $field = $fields[$key] ?? null;

            if ($field === null || $value === null || $value === '') {
                continue;
            }

            $clean[$key] = match ($field['kind']) {
                'int' => (int) $value,
                'decimal' => (float) $value,
                'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                'multiselect' => array_values(array_filter(
                    is_array($value) ? $value : [$value],
                    fn ($v) => in_array($v, array_column($field['options'] ?? [], 'value'), true),
                )),
                default => (string) $value,
            };
        }

        return $clean;
    }

    /**
     * Validation rules for `specs.*`, derived from the schema.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?int $propertyTypeId): array
    {
        $rules = [];

        foreach (self::fieldsForType($propertyTypeId) as $key => $field) {
            $rule = ['nullable'];

            switch ($field['kind']) {
                case 'int':
                    $rule[] = 'integer';
                    $rule[] = 'min:'.($field['min'] ?? 0);
                    $rule[] = 'max:'.($field['max'] ?? 999999);
                    break;
                case 'decimal':
                    $rule[] = 'numeric';
                    $rule[] = 'min:'.($field['min'] ?? 0);
                    break;
                case 'bool':
                    $rule[] = 'boolean';
                    break;
                case 'select':
                    $rule[] = 'string';
                    $rule[] = 'in:'.implode(',', array_column($field['options'] ?? [], 'value'));
                    break;
                case 'multiselect':
                    $rule[] = 'array';
                    $rules["specs.{$key}.*"] = [
                        'string',
                        'in:'.implode(',', array_column($field['options'] ?? [], 'value')),
                    ];
                    break;
                default:
                    $rule[] = 'string';
                    $rule[] = 'max:'.($field['max'] ?? 255);
            }

            $rules["specs.{$key}"] = $rule;
        }

        return $rules;
    }
}
