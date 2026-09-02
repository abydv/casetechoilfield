<?php

namespace App\Models;

use App\Entities\Media;
use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table         = 'media';
    protected $primaryKey    = 'id';
    protected $returnType    = Media::class;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'folder_id', 'filename', 'original_filename', 'mime_type',
        'size_bytes', 'width', 'height', 'alt_text', 'caption',
        'description', 'uploaded_by', 'created_at',
    ];

    public function variants(int $mediaId): array
    {
        return $this->db->table('media_variants')->where('media_id', $mediaId)->get()->getResultArray();
    }
}
