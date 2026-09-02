<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use App\Models\EnquiryModel;
use App\Services\AuditLog;

/**
 * Handles "Request a Quote" submissions from product/service detail pages
 * into the enquiries table (docs/database-schema.md §13, spec §15).
 */
class EnquiryController extends BaseController
{
    public function submit()
    {
        // Honeypot: a real visitor never fills this hidden field in; a
        // simple bot filling every field will. Silently "succeed" so the
        // bot doesn't learn anything, without writing a row.
        if ((string) $this->request->getPost('website') !== '') {
            return redirect()->back()->with('success', 'Thank you — your enquiry has been received.');
        }

        $rules = [
            'name'  => 'required|max_length[150]',
            'email' => 'required|valid_email|max_length[191]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('enquiry_error', 'Please provide your name and a valid email address.');
        }

        $productId = $this->request->getPost('product_id') ?: null;
        $serviceId = $this->request->getPost('service_id') ?: null;

        $message = (string) $this->request->getPost('message');
        $country = $this->request->getPost('country');
        if ($country) {
            $message = "Country: {$country}\n\n{$message}";
        }

        $id = (new EnquiryModel())->insert([
            'product_id' => $productId,
            'service_id' => $serviceId,
            'name'       => $this->request->getPost('name'),
            'company'    => $this->request->getPost('company'),
            'email'      => $this->request->getPost('email'),
            'phone'      => $this->request->getPost('phone'),
            'quantity'   => $this->request->getPost('quantity'),
            'message'    => $message,
            'source_url' => $this->request->getPost('source_url') ?: (string) $this->request->getServer('HTTP_REFERER'),
            'status'     => 'new',
        ], true);

        AuditLog::record(null, 'enquiries.create', 'enquiries', 'enquiries', (int) $id);

        return redirect()->back()->with('enquiry_success', 'Thank you — your enquiry has been received. Our team will contact you shortly.');
    }
}
