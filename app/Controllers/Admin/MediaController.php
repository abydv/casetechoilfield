<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MediaFolderModel;
use App\Models\MediaModel;
use App\Services\AuditLog;
use App\Services\MediaService;
use App\Traits\ContentCrudHelpers;

/**
 * Centralized media library (docs/cms-specification.md §8). Upload
 * itself is handled by App\Services\MediaService (shared with every
 * content module's inline upload fields) — this controller is the
 * browse/manage surface: search, folders, alt text, replace, delete.
 */
class MediaController extends BaseController
{
    use ContentCrudHelpers;

    private MediaModel $media;
    private MediaFolderModel $folders;

    public function __construct()
    {
        $this->media   = new MediaModel();
        $this->folders = new MediaFolderModel();
    }

    public function index()
    {
        $search = $this->request->getGet('q');
        $folderId = $this->request->getGet('folder') ?: null;

        $items = $this->media->forListing($search, $folderId ? (int) $folderId : null);

        return view('admin/media/index', [
            'items'    => $this->withUrls($items),
            'pager'    => $this->media->pager,
            'folders'  => $this->folders->orderBy('name')->findAll(),
            'search'   => $search,
            'folderId' => $folderId,
        ]);
    }

    public function upload()
    {
        $folderId = $this->request->getPost('folder_id') ?: null;
        $files = $this->request->getFileMultiple('files') ?? [];
        $service = new MediaService();
        $uploaded = 0;
        $errors = [];

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            try {
                $service->upload($file, $folderId ? (int) $folderId : null, $this->currentUserId());
                $uploaded++;
            } catch (\Throwable $e) {
                $errors[] = $file->getClientName() . ': ' . $e->getMessage();
            }
        }

        AuditLog::record($this->currentUserId(), 'media.upload', 'media', 'media', null, null, ['count' => $uploaded]);

        $session = session();
        if ($uploaded > 0) {
            $session->setFlashdata('success', "{$uploaded} file(s) uploaded.");
        }
        if (! empty($errors)) {
            $session->setFlashdata('error', implode('; ', $errors));
        }

        return redirect()->to('/admin/media');
    }

    public function update($id)
    {
        $item = $this->media->find((int) $id);
        if (! $item) {
            return redirect()->to('/admin/media')->with('error', 'File not found.');
        }

        $data = [
            'alt_text'    => $this->request->getPost('alt_text'),
            'caption'     => $this->request->getPost('caption'),
            'description' => $this->request->getPost('description'),
            'folder_id'   => $this->request->getPost('folder_id') ?: null,
        ];
        $this->media->update((int) $id, $data);
        $this->logAction('media.update', 'media', (int) $id, null, $data);

        return redirect()->to('/admin/media')->with('success', 'Updated.');
    }

    public function delete($id)
    {
        try {
            $deleted = (new MediaService())->delete((int) $id);
            if ($deleted) {
                $this->logAction('media.delete', 'media', (int) $id, null, null);

                return redirect()->to('/admin/media')->with('success', 'File deleted.');
            }

            return redirect()->to('/admin/media')->with('error', 'File not found.');
        } catch (\RuntimeException $e) {
            return redirect()->to('/admin/media')->with('error', $e->getMessage());
        }
    }

    public function storeFolder()
    {
        if (! $this->validate(['name' => 'required|max_length[150]'])) {
            return redirect()->to('/admin/media')->with('error', 'Folder name is required.');
        }

        $name = $this->request->getPost('name');
        $this->folders->insert([
            'name' => $name,
            'slug' => $this->uniqueSlug('media_folders', $name),
        ]);

        return redirect()->to('/admin/media')->with('success', 'Folder created.');
    }

    private function withUrls(iterable $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $out[] = ['media' => $item, 'url' => base_url('uploads/' . $item->filename)];
        }

        return $out;
    }
}
