<?php

namespace App\Models;

use CodeIgniter\Model;

class MediaFolderModel extends Model
{
    protected $table         = 'media_folders';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['parent_id', 'name', 'slug'];

    protected $validationRules = [
        'name' => 'required|max_length[150]',
    ];
}
