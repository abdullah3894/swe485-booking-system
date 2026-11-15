<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Models\Item;
use Illuminate\Database\Eloquent\Collection;

/**
 * The only code that touches the items table (A-CAT).
 */
class ItemCatalogService
{
    /**
     * All items, active and deactivated, ordered by name (manage screen).
     *
     * @return Collection<int, Item>
     */
    public function listAll(): Collection
    {
        return Item::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Only the items that can still be booked.
     *
     * @return Collection<int, Item>
     */
    public function listActive(): Collection
    {
        return Item::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * The requested items keyed by id; unknown ids are simply absent.
     *
     * @param  array<int, int|string>  $ids
     * @return array<int, Item>
     */
    public function getByIds(array $ids): array
    {
        return Item::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * Whether the item exists and can still be booked.
     */
    public function isActive(int $id): bool
    {
        return Item::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Create a new item; new items are always active.
     */
    public function create(string $name, string $type): Item
    {
        return Item::create([
            'name' => $name,
            'type' => $type,
            'is_active' => true,
        ]);
    }

    /**
     * Change an item's name and type.
     */
    public function update(int $id, string $name, string $type): Item
    {
        $item = Item::query()->findOrFail($id);

        $item->update([
            'name' => $name,
            'type' => $type,
        ]);

        return $item;
    }

    /**
     * Deactivate an item: it stays in the database, past bookings keep their item.
     */
    public function deactivate(int $id): void
    {
        $item = Item::query()->findOrFail($id);

        $item->update(['is_active' => false]);
    }
}
