<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Media extends Entity
{
    protected $attributes = [
        'id'                => null,
        'folder_id'         => null,
        'filename'          => null,
        'original_filename' => null,
        'mime_type'         => null,
        'size_bytes'        => null,
        'width'             => null,
        'height'            => null,
        'alt_text'          => null,
        'caption'           => null,
        'description'       => null,
        'uploaded_by'       => null,
        'created_at'        => null,
    ];

    public function url(): string
    {
        return base_url('uploads/' . $this->attributes['filename']);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->attributes['mime_type'], 'image/');
    }
}
