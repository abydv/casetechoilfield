<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EnquiryModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * Leads / enquiry management (docs/cms-specification.md §9,
 * docs/database-schema.md §13) — one inbox for enquiries raised from
 * product pages, service pages, and (later) the generic form builder.
 */
class EnquiryController extends BaseController
{
    use ContentCrudHelpers;

    private EnquiryModel $enquiries;

    private const STATUSES = ['new', 'contacted', 'qualified', 'quoted', 'won', 'lost', 'spam', 'closed'];

    public function __construct()
    {
        $this->enquiries = new EnquiryModel();
    }

    public function index()
    {
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('q');

        $builder = $this->enquiries->orderBy('created_at', 'DESC');
        if ($status) {
            $builder = $builder->where('status', $status);
        }
        if ($search) {
            $builder = $builder->groupStart()
                ->like('name', $search)->orLike('email', $search)->orLike('company', $search)
                ->groupEnd();
        }

        $enquiries = $builder->paginate(25);
        $db = Database::connect();

        foreach ($enquiries as &$e) {
            if (! empty($e['product_id'])) {
                $e['related_label'] = 'Product: ' . ($db->table('products')->select('name')->where('id', $e['product_id'])->get()->getRow()->name ?? '—');
            } elseif (! empty($e['service_id'])) {
                $e['related_label'] = 'Service: ' . ($db->table('services')->select('name')->where('id', $e['service_id'])->get()->getRow()->name ?? '—');
            } else {
                $e['related_label'] = 'General enquiry';
            }
        }

        return view('admin/enquiries/index', [
            'enquiries' => $enquiries,
            'pager'     => $this->enquiries->pager,
            'status'    => $status,
            'search'    => $search,
            'statuses'  => self::STATUSES,
            'newCount'  => $this->enquiries->where('status', 'new')->countAllResults(),
        ]);
    }

    public function show($id)
    {
        $enquiry = $this->enquiries->find((int) $id);
        if (! $enquiry) {
            return redirect()->to('/admin/enquiries')->with('error', 'Enquiry not found.');
        }

        $db = Database::connect();

        $related = null;
        if (! empty($enquiry['product_id'])) {
            $related = ['type' => 'Product', 'row' => $db->table('products')->select('id, name, slug')->where('id', $enquiry['product_id'])->get()->getRowArray()];
        } elseif (! empty($enquiry['service_id'])) {
            $related = ['type' => 'Service', 'row' => $db->table('services')->select('id, name, slug')->where('id', $enquiry['service_id'])->get()->getRowArray()];
        }

        $notes = $db->table('enquiry_notes en')
            ->select('en.*, u.name as user_name')
            ->join('users u', 'u.id = en.user_id', 'left')
            ->where('en.enquiry_id', $id)
            ->orderBy('en.created_at', 'DESC')
            ->get()->getResultArray();

        $users = $db->table('users')->select('id, name')->where('status', 'active')->orderBy('name')->get()->getResultArray();

        return view('admin/enquiries/show', [
            'enquiry'  => $enquiry,
            'related'  => $related,
            'notes'    => $notes,
            'users'    => $users,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update($id)
    {
        $enquiry = $this->enquiries->find((int) $id);
        if (! $enquiry) {
            return redirect()->to('/admin/enquiries')->with('error', 'Enquiry not found.');
        }

        $before = $enquiry;
        $data = [
            'status'         => in_array($this->request->getPost('status'), self::STATUSES, true) ? $this->request->getPost('status') : $enquiry['status'],
            'assigned_to'    => $this->request->getPost('assigned_to') ?: null,
            'follow_up_date' => $this->request->getPost('follow_up_date') ?: null,
        ];

        $this->enquiries->update((int) $id, $data);
        $this->logAction('enquiries.update', 'enquiries', (int) $id, $before, $data);

        $note = trim((string) $this->request->getPost('note'));
        if ($note !== '') {
            Database::connect()->table('enquiry_notes')->insert([
                'enquiry_id' => (int) $id,
                'user_id'    => $this->currentUserId(),
                'note'       => $note,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to('/admin/enquiries/' . $id)->with('success', 'Enquiry updated.');
    }

    public function export()
    {
        $rows = $this->enquiries->orderBy('created_at', 'DESC')->findAll();

        $filename = 'enquiries-' . date('Y-m-d') . '.csv';
        $this->response->setHeader('Content-Type', 'text/csv');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['ID', 'Date', 'Name', 'Company', 'Email', 'Phone', 'Quantity', 'Message', 'Status', 'Source URL']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['id'], $row['created_at'], $row['name'], $row['company'], $row['email'],
                $row['phone'], $row['quantity'], $row['message'], $row['status'], $row['source_url'],
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response->setBody($csv);
    }
}
