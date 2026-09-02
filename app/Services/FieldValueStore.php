<?php

namespace App\Services;

use App\Models\CustomFieldModel;
use Config\Database;

/**
 * Reads/writes custom_field_values for a content_entries row, routing
 * each value to the correctly-typed column based on the field's
 * field_type (docs/database-schema.md §3) — this is what keeps custom
 * content type values indexable instead of one stringly-typed blob.
 *
 * Column routing:
 *   value_text    - text, email, phone, url, color, icon, select, radio, time
 *   value_json    - textarea, richtext (a JSON-encoded *string*, to avoid
 *                   value_text's length limit) and multiselect, gallery,
 *                   repeater (a JSON-encoded *array*) — kept as two
 *                   separate lists (not one) because they decode to
 *                   different PHP types; conflating them made a missing
 *                   textarea/richtext value decode to [] instead of ''.
 *   value_int     - image, pdf, file, relationship (a media_id or entity id)
 *   value_decimal - number
 *   value_date    - date
 *   checkbox      - value_text ('1' or '')
 *   video         - value_text (a URL, not an upload)
 */
class FieldValueStore
{
    private const SCALAR_JSON_TYPES = ['textarea', 'richtext'];
    private const ARRAY_JSON_TYPES  = ['multiselect', 'gallery', 'repeater'];
    private const INT_TYPES         = ['image', 'pdf', 'file', 'relationship'];

    /** @return array<string, mixed> field_key => value */
    public function getByFieldKey(int $entryId, array $fields): array
    {
        $db = Database::connect();
        $rows = $db->table('custom_field_values')->where('content_entry_id', $entryId)->get()->getResultArray();
        $byFieldId = array_column($rows, null, 'custom_field_id');

        $out = [];
        foreach ($fields as $field) {
            $row = $byFieldId[$field['id']] ?? null;
            $out[$field['field_key']] = $row ? $this->extract($field['field_type'], $row) : null;
        }

        return $out;
    }

    private function extract(string $fieldType, array $row): mixed
    {
        if (in_array($fieldType, self::ARRAY_JSON_TYPES, true)) {
            return json_decode($row['value_json'] ?? '', true) ?? [];
        }
        if (in_array($fieldType, self::SCALAR_JSON_TYPES, true)) {
            $decoded = json_decode($row['value_json'] ?? 'null', true);

            return is_string($decoded) ? $decoded : '';
        }
        if (in_array($fieldType, self::INT_TYPES, true)) {
            return $row['value_int'] !== null ? (int) $row['value_int'] : null;
        }
        if ($fieldType === 'number') {
            return $row['value_decimal'];
        }
        if ($fieldType === 'date') {
            return $row['value_date'];
        }

        return $row['value_text'];
    }

    /**
     * Stores one field's value. $rawValue's shape depends on field_type:
     * scalar for text-like/number/date, array for multiselect/gallery/repeater,
     * int|null for image/pdf/file/relationship.
     */
    public function setValue(int $entryId, array $field, mixed $rawValue): void
    {
        $db = Database::connect();
        $column = $this->columnFor($field['field_type']);

        $data = [
            'value_text'    => null,
            'value_int'     => null,
            'value_decimal' => null,
            'value_date'    => null,
            'value_json'    => null,
        ];

        if ($column === 'value_json') {
            $data['value_json'] = in_array($field['field_type'], self::ARRAY_JSON_TYPES, true)
                ? json_encode($rawValue ?? [])
                : json_encode($rawValue !== null ? (string) $rawValue : '');
        } elseif ($column === 'value_int') {
            $data['value_int'] = $rawValue !== null && $rawValue !== '' ? (int) $rawValue : null;
        } elseif ($column === 'value_decimal') {
            $data['value_decimal'] = $rawValue !== null && $rawValue !== '' ? (float) $rawValue : null;
        } elseif ($column === 'value_date') {
            $data['value_date'] = $rawValue ?: null;
        } else {
            $data['value_text'] = $rawValue !== null ? (string) $rawValue : null;
        }

        $existing = $db->table('custom_field_values')
            ->where('content_entry_id', $entryId)->where('custom_field_id', $field['id'])
            ->get()->getRowArray();

        if ($existing) {
            $db->table('custom_field_values')->where('id', $existing['id'])->update($data);

            return;
        }

        $data['content_entry_id'] = $entryId;
        $data['custom_field_id'] = $field['id'];
        $db->table('custom_field_values')->insert($data);
    }

    private function columnFor(string $fieldType): string
    {
        if (in_array($fieldType, self::ARRAY_JSON_TYPES, true) || in_array($fieldType, self::SCALAR_JSON_TYPES, true)) {
            return 'value_json';
        }
        if (in_array($fieldType, self::INT_TYPES, true)) {
            return 'value_int';
        }
        if ($fieldType === 'number') {
            return 'value_decimal';
        }
        if ($fieldType === 'date') {
            return 'value_date';
        }

        return 'value_text';
    }
}
