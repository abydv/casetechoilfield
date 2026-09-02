<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FormFieldModel;
use App\Models\FormModel;
use App\Models\FormSubmissionModel;
use App\Traits\ContentCrudHelpers;

/**
 * Form Builder (docs/cms-specification.md §9, spec §22). Public
 * rendering/submission handling lives in Site\FormController — a built
 * form is immediately live at /forms/{slug}.
 */
class FormController extends BaseController
{
    use ContentCrudHelpers;

    private const FIELD_TYPES = ['text', 'email', 'phone', 'textarea', 'dropdown', 'checkbox', 'radio', 'file', 'date', 'number', 'hidden'];

    private FormModel $forms;
    private FormFieldModel $fields;

    public function __construct()
    {
        $this->forms  = new FormModel();
        $this->fields = new FormFieldModel();
    }

    public function index()
    {
        return view('admin/forms/index', ['forms' => $this->forms->orderBy('name')->findAll()]);
    }

    public function create()
    {
        return view('admin/forms/form', ['formRow' => null, 'fields' => [], 'fieldTypes' => self::FIELD_TYPES]);
    }

    public function store()
    {
        if (! $this->validate(['name' => 'required|max_length[150]'])) {
            return redirect()->back()->withInput()->with('error', 'A form name is required.');
        }

        $name = $this->request->getPost('name');
        $id = $this->forms->insert($this->collectFormData($name), true);

        $this->logAction('forms.create', 'forms', (int) $id, null, ['name' => $name]);

        return redirect()->to('/admin/forms/' . $id . '/edit')->with('success', 'Form created. Now add fields below.');
    }

    public function edit($id)
    {
        $form = $this->forms->find((int) $id);
        if (! $form) {
            return redirect()->to('/admin/forms')->with('error', 'Form not found.');
        }

        return view('admin/forms/form', [
            'formRow'    => $form,
            'fields'     => $this->fields->forForm((int) $id),
            'fieldTypes' => self::FIELD_TYPES,
        ]);
    }

    public function update($id)
    {
        $form = $this->forms->find((int) $id);
        if (! $form) {
            return redirect()->to('/admin/forms')->with('error', 'Form not found.');
        }
        if (! $this->validate(['name' => 'required|max_length[150]'])) {
            return redirect()->back()->withInput()->with('error', 'A form name is required.');
        }

        $before = $form;
        $data = $this->collectFormData($this->request->getPost('name'), $form['slug']);
        $this->forms->update((int) $id, $data);
        $this->logAction('forms.update', 'forms', (int) $id, $before, $data);

        return redirect()->to('/admin/forms/' . $id . '/edit')->with('success', 'Form saved.');
    }

    public function delete($id)
    {
        $this->fields->where('form_id', $id)->delete();
        $this->forms->delete((int) $id);
        $this->logAction('forms.delete', 'forms', (int) $id, null, null);

        return redirect()->to('/admin/forms')->with('success', 'Form deleted.');
    }

    public function addField($formId)
    {
        $form = $this->forms->find((int) $formId);
        if (! $form) {
            return redirect()->to('/admin/forms')->with('error', 'Form not found.');
        }

        $label = (string) $this->request->getPost('label');
        $fieldType = $this->request->getPost('field_type');
        if (! in_array($fieldType, self::FIELD_TYPES, true) || $label === '') {
            return redirect()->to('/admin/forms/' . $formId . '/edit')->with('error', 'Enter a label and a valid field type.');
        }

        $maxOrder = (int) ($this->fields->where('form_id', $formId)->selectMax('sort_order')->first()['sort_order'] ?? -1);
        $options = trim((string) $this->request->getPost('options'));

        $this->fields->insert([
            'form_id'          => (int) $formId,
            'field_key'        => $this->slugify($label) . '_' . (($maxOrder + 1)),
            'label'            => $label,
            'field_type'       => $fieldType,
            'options'          => $options !== '' ? json_encode(array_map('trim', explode(',', $options))) : null,
            'is_required'      => $this->request->getPost('is_required') ? 1 : 0,
            'sort_order'       => $maxOrder + 1,
        ]);

        return redirect()->to('/admin/forms/' . $formId . '/edit')->with('success', 'Field added.');
    }

    public function deleteField($formId, $fieldId)
    {
        $this->fields->where('form_id', $formId)->where('id', $fieldId)->delete();

        return redirect()->to('/admin/forms/' . $formId . '/edit')->with('success', 'Field removed.');
    }

    public function submissions($formId)
    {
        $form = $this->forms->find((int) $formId);
        if (! $form) {
            return redirect()->to('/admin/forms')->with('error', 'Form not found.');
        }

        $submissionModel = new FormSubmissionModel();
        $submissions = $submissionModel->forForm((int) $formId);
        foreach ($submissions as &$s) {
            $s['data'] = json_decode($s['data'], true) ?: [];
        }

        return view('admin/forms/submissions', [
            'form'        => $form,
            'submissions' => $submissions,
            'pager'       => $submissionModel->pager,
        ]);
    }

    private function collectFormData(string $name, ?string $existingSlug = null): array
    {
        $recipients = array_filter(array_map('trim', explode(',', (string) $this->request->getPost('recipient_emails'))));

        return [
            'name'                   => $name,
            'slug'                   => $existingSlug ?: $this->uniqueSlug('forms', $name),
            'recipient_emails'       => json_encode($recipients),
            'success_message'        => $this->request->getPost('success_message') ?: 'Thank you — your submission has been received.',
            'redirect_url'           => $this->request->getPost('redirect_url') ?: null,
            'store_in_db'            => 1,
            'captcha_provider'       => $this->request->getPost('captcha_provider') ?: 'none',
            'auto_response_enabled'  => $this->request->getPost('auto_response_enabled') ? 1 : 0,
            'auto_response_subject'  => $this->request->getPost('auto_response_subject'),
            'auto_response_body'     => $this->request->getPost('auto_response_body'),
        ];
    }
}
