<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomFieldModel extends Model
{
    protected $table         = 'custom_fields';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'content_type_id', 'field_key', 'label', 'field_type', 'options',
        'validation_rules', 'sort_order', 'is_required',
    ];

    /** The full field_type palette from docs/database-schema.md §3 / spec §20. */
    public const FIELD_TYPES = [
        'text', 'textarea', 'richtext', 'number', 'email', 'phone', 'url', 'date', 'time',
        'image', 'gallery', 'video', 'pdf', 'file', 'select', 'multiselect', 'checkbox',
        'radio', 'color', 'icon', 'relationship', 'repeater',
    ];

    public function forType(int $contentTypeId): array
    {
        return $this->where('content_type_id', $contentTypeId)->orderBy('sort_order', 'ASC')->findAll();
    }
}
