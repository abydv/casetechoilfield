<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MenuItemModel;
use App\Models\MenuModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * Menu builder (docs/cms-specification.md §3). Reordering is done via a
 * numeric "sort order" field rather than JS drag-and-drop for this first
 * pass — the data model (menu_items.sort_order) is exactly what a future
 * drag-and-drop UI would write to, so upgrading the interaction later
 * doesn't touch the schema or resolvedTree() rendering logic.
 */
class MenuController extends BaseController
{
    use ContentCrudHelpers;

    private MenuModel $menus;
    private MenuItemModel $items;

    public function __construct()
    {
        $this->menus = new MenuModel();
        $this->items = new MenuItemModel();
    }

    public function index()
    {
        return view('admin/menus/index', ['menus' => $this->menus->orderBy('name')->findAll()]);
    }

    public function store()
    {
        if (! $this->validate(['name' => 'required|max_length[100]'])) {
            return redirect()->to('/admin/menus')->with('error', 'Menu name is required.');
        }

        $name = $this->request->getPost('name');
        $id = $this->menus->insert([
            'name'     => $name,
            'slug'     => $this->uniqueSlug('menus', $name),
            'location' => $this->request->getPost('location'),
        ], true);

        $this->logAction('menus.create', 'menus', (int) $id, null, ['name' => $name]);

        return redirect()->to('/admin/menus/' . $id . '/edit')->with('success', 'Menu created.');
    }

    public function edit($id)
    {
        $menu = $this->menus->find((int) $id);
        if (! $menu) {
            return redirect()->to('/admin/menus')->with('error', 'Menu not found.');
        }

        $db = Database::connect();

        return view('admin/menus/edit', [
            'menu'     => $menu,
            'items'    => $this->items->where('menu_id', $id)->orderBy('sort_order')->findAll(),
            'pages'    => $db->table('pages')->select('id, title')->orderBy('title')->get()->getResultArray(),
            'products' => $db->table('products')->select('id, name')->orderBy('name')->get()->getResultArray(),
            'services' => $db->table('services')->select('id, name')->orderBy('name')->get()->getResultArray(),
            'projects' => $db->table('projects')->select('id, title')->orderBy('title')->get()->getResultArray(),
        ]);
    }

    public function delete($id)
    {
        $menu = $this->menus->find((int) $id);
        if (! $menu) {
            return redirect()->to('/admin/menus')->with('error', 'Menu not found.');
        }

        $this->items->where('menu_id', $id)->delete();
        $this->menus->delete((int) $id);
        $this->logAction('menus.delete', 'menus', (int) $id, $menu, null);

        return redirect()->to('/admin/menus')->with('success', 'Menu deleted.');
    }

    public function addItem($menuId)
    {
        $menu = $this->menus->find((int) $menuId);
        if (! $menu) {
            return redirect()->to('/admin/menus')->with('error', 'Menu not found.');
        }

        $linkType = $this->request->getPost('link_type');
        $target = $linkType === 'custom_url' ? null : $this->request->getPost('link_target');

        $maxRow = $this->items->where('menu_id', $menuId)->selectMax('sort_order')->first();
        $maxOrder = (int) ($maxRow['sort_order'] ?? -1);

        $this->items->insert([
            'menu_id'       => (int) $menuId,
            'parent_id'     => $this->request->getPost('parent_id') ?: null,
            'label'         => $this->request->getPost('label'),
            'link_type'     => $linkType,
            'link_target'   => $target,
            'url_override'  => $linkType === 'custom_url' ? $this->request->getPost('custom_url') : null,
            'open_new_tab'  => $this->request->getPost('open_new_tab') ? 1 : 0,
            'sort_order'    => $maxOrder + 1,
        ]);

        return redirect()->to('/admin/menus/' . $menuId . '/edit')->with('success', 'Menu item added.');
    }

    public function updateItem($menuId, $itemId)
    {
        $item = $this->items->where('menu_id', $menuId)->find((int) $itemId);
        if (! $item) {
            return redirect()->to('/admin/menus/' . $menuId . '/edit')->with('error', 'Item not found.');
        }

        $this->items->update((int) $itemId, [
            'sort_order' => (int) ($this->request->getPost('sort_order') ?: 0),
            'parent_id'  => $this->request->getPost('parent_id') ?: null,
        ]);

        return redirect()->to('/admin/menus/' . $menuId . '/edit')->with('success', 'Menu item updated.');
    }

    public function deleteItem($menuId, $itemId)
    {
        $this->items->where('menu_id', $menuId)->where('id', $itemId)->delete();

        return redirect()->to('/admin/menus/' . $menuId . '/edit')->with('success', 'Menu item removed.');
    }
}
