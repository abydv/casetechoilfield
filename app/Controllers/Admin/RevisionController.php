<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PageModel;
use App\Models\ProductModel;
use App\Models\ProjectModel;
use App\Models\ServiceModel;
use App\Services\PageBodyStore;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * Revision history (docs/database-schema.md §15, spec §30): one generic
 * viewer/restorer for every revisionable content type, since they all
 * share the same revisions table shape. Restoring an old revision first
 * snapshots the current state as a new revision, so restore itself is
 * never destructive.
 */
class RevisionController extends BaseController
{
    use ContentCrudHelpers;

    private const TYPE_CONFIG = [
        'product' => ['model' => ProductModel::class, 'label' => 'Product', 'editUrl' => 'admin/products/'],
        'service' => ['model' => ServiceModel::class, 'label' => 'Service', 'editUrl' => 'admin/services/'],
        'project' => ['model' => ProjectModel::class, 'label' => 'Project', 'editUrl' => 'admin/projects/'],
        'page'    => ['model' => PageModel::class, 'label' => 'Page', 'editUrl' => 'admin/pages/'],
    ];

    public function index($type, $id)
    {
        $config = self::TYPE_CONFIG[$type] ?? null;
        if (! $config) {
            return redirect()->to('/admin')->with('error', 'Unknown content type.');
        }

        $model = new $config['model']();
        $record = $model->find((int) $id);
        if (! $record) {
            return redirect()->to('/admin')->with('error', 'Record not found.');
        }

        $revisions = Database::connect()->table('revisions r')
            ->select('r.*, u.name as user_name')
            ->join('users u', 'u.id = r.created_by', 'left')
            ->where('r.revisionable_type', $type)
            ->where('r.revisionable_id', $id)
            ->orderBy('r.created_at', 'DESC')
            ->get()->getResultArray();

        foreach ($revisions as &$rev) {
            $rev['snapshot'] = json_decode($rev['data'], true) ?: [];
        }

        return view('admin/revisions/index', [
            'type'      => $type,
            'typeLabel' => $config['label'],
            'editUrl'   => site_url($config['editUrl'] . $id . '/edit'),
            'recordId'  => $id,
            'revisions' => $revisions,
        ]);
    }

    public function restore($type, $id, $revisionId)
    {
        $config = self::TYPE_CONFIG[$type] ?? null;
        if (! $config) {
            return redirect()->to('/admin')->with('error', 'Unknown content type.');
        }

        $revision = Database::connect()->table('revisions')
            ->where('id', $revisionId)->where('revisionable_type', $type)->where('revisionable_id', $id)
            ->get()->getRowArray();
        if (! $revision) {
            return redirect()->back()->with('error', 'Revision not found.');
        }

        $model = new $config['model']();
        $current = $model->find((int) $id);
        if (! $current) {
            return redirect()->to('/admin')->with('error', 'Record not found.');
        }

        // Snapshot current state first — restoring is itself reversible.
        // For pages, the current snapshot must include the richtext body
        // (see App\Services\PageBodyStore) — it lives outside the `pages`
        // row, so $current->toArray() alone would silently lose it.
        $currentSnapshot = $current->toArray();
        if ($type === 'page') {
            $currentSnapshot['_richtext_body'] = (new PageBodyStore())->get((int) $id);
        }
        $this->writeRevision($type, (int) $id, $currentSnapshot, $this->currentUserId());

        $snapshot = json_decode($revision['data'], true) ?: [];
        unset($snapshot['id'], $snapshot['created_at'], $snapshot['updated_at'], $snapshot['deleted_at']);

        $restoredBody = null;
        if ($type === 'page' && array_key_exists('_richtext_body', $snapshot)) {
            $restoredBody = $snapshot['_richtext_body'];
            unset($snapshot['_richtext_body']);
        }

        $model->update((int) $id, $snapshot);
        if ($restoredBody !== null) {
            (new PageBodyStore())->save((int) $id, $restoredBody);
        }

        $this->logAction("{$type}.restore", $type . 's', (int) $id, $current->toArray(), $snapshot);

        return redirect()->to($config['editUrl'] . $id . '/edit')->with('success', 'Restored to the selected revision.');
    }
}
