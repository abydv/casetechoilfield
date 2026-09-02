<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuModel extends Model
{
    protected $table         = 'menus';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['name', 'slug', 'location'];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
    ];

    public function findBySlug(string $slug)
    {
        return $this->where('slug', $slug)->first();
    }

    public function findByLocation(string $location)
    {
        return $this->where('location', $location)->first();
    }
}
