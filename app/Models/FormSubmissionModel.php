<?php

namespace App\Models;

use CodeIgniter\Model;

class FormSubmissionModel extends Model
{
    protected $table         = 'form_submissions';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['form_id', 'data', 'source_url', 'ip_address', 'user_agent', 'status'];

    public function forForm(int $formId, int $perPage = 25)
    {
        return $this->where('form_id', $formId)->orderBy('created_at', 'DESC')->paginate($perPage);
    }
}
