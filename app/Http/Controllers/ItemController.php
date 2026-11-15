<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Modules\Catalog\ItemCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ItemController extends Controller
{
    public function __construct(private readonly ItemCatalogService $catalog)
    {
    }

    /**
     * Manage Items: every item, active and deactivated.
     */
    public function index(): View
    {
        return view('items.index', [
            'items' => $this->catalog->listAll(),
        ]);
    }

    /**
     * Add an item.
     */
    public function store(ItemRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->catalog->create((string) $data['name'], (string) $data['type']);

        return redirect('/items');
    }

    /**
     * Edit or deactivate an item, chosen by the hidden action field.
     */
    public function update(ItemRequest $request, int $item): RedirectResponse
    {
        $data = $request->validated();

        if (($data['action'] ?? null) === 'deactivate') {
            $this->catalog->deactivate($item);
        } else {
            $this->catalog->update($item, (string) $data['name'], (string) $data['type']);
        }

        return redirect('/items');
    }
}
