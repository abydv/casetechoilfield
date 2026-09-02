<?php

namespace App\Models;

use CodeIgniter\Model;

class FormFieldModel extends Model
{
    protected $table         = 'form_fields';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'form_id', 'field_key', 'label', 'field_type', 'options',
        'is_required', 'sort_order', 'validation_rules',
    ];

    protected $validationRules = [
        'label'     => 'required|max_length[150]',
        'field_type' => 'required|in_list[text,email,phone,textarea,dropdown,checkbox,radio,file,date,number,hidden]',
    ];

    public function forForm(int $formId): array
    {
        return $this->where('form_id', $formId)->orderBy('sort_order', 'ASC')->findAll();
    }
}
