<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\ItemCatalogService;
use App\Modules\Catalog\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private ItemCatalogService $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalog = new ItemCatalogService();
    }

    public function test_list_all_returns_active_and_deactivated_items_ordered_by_name(): void
    {
        Item::factory()->create(['name' => 'Pitch B', 'type' => 'field']);
        Item::factory()->create(['name' => 'Racket', 'type' => 'equipment', 'is_active' => false]);
        Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $this->assertSame(
            ['Pitch A', 'Pitch B', 'Racket'],
            $this->catalog->listAll()->pluck('name')->all()
        );
    }

    public function test_list_active_excludes_deactivated_items(): void
    {
        Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);
        Item::factory()->create(['name' => 'Racket', 'type' => 'equipment', 'is_active' => false]);

        $this->assertSame(['Pitch A'], $this->catalog->listActive()->pluck('name')->all());
    }

    public function test_get_by_ids_returns_items_keyed_by_id(): void
    {
        $pitch = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);
        $racket = Item::factory()->create(['name' => 'Racket', 'type' => 'equipment']);
        $unasked = Item::factory()->create(['name' => 'Pitch B', 'type' => 'field']);

        $items = $this->catalog->getByIds([$pitch->id, $racket->id]);

        $this->assertSame([$pitch->id, $racket->id], array_keys($items));
        $this->assertTrue($items[$pitch->id]->is($pitch));
        $this->assertTrue($items[$racket->id]->is($racket));
        $this->assertArrayNotHasKey($unasked->id, $items);
    }

    public function test_get_by_ids_returns_an_empty_array_for_no_ids(): void
    {
        Item::factory()->create();

        $this->assertSame([], $this->catalog->getByIds([]));
    }

    public function test_is_active_is_true_for_an_active_item(): void
    {
        $item = Item::factory()->create(['is_active' => true]);

        $this->assertTrue($this->catalog->isActive($item->id));
    }

    public function test_is_active_is_false_for_a_deactivated_item(): void
    {
        $item = Item::factory()->create(['is_active' => false]);

        $this->assertFalse($this->catalog->isActive($item->id));
    }

    public function test_is_active_is_false_for_an_unknown_id(): void
    {
        $this->assertFalse($this->catalog->isActive(9999));
    }

    public function test_create_always_stores_an_active_item(): void
    {
        $item = $this->catalog->create('Pitch C', 'field');

        $this->assertSame('Pitch C', $item->name);
        $this->assertSame('field', $item->type);
        $this->assertTrue($item->is_active);
        $this->assertTrue($item->fresh()->is_active);
        $this->assertDatabaseHas('items', ['name' => 'Pitch C', 'type' => 'field']);
    }

    public function test_update_changes_the_name_and_type(): void
    {
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $updated = $this->catalog->update($item->id, 'Pitch B', 'equipment');

        $this->assertSame($item->id, $updated->id);
        $this->assertSame('Pitch B', $updated->fresh()->name);
        $this->assertSame('equipment', $updated->fresh()->type);
    }

    public function test_deactivate_keeps_the_row_and_sets_is_active_false(): void
    {
        $item = Item::factory()->create(['is_active' => true]);

        $this->catalog->deactivate($item->id);

        $this->assertFalse($item->fresh()->is_active);
        $this->assertDatabaseCount('items', 1);
        $this->assertDatabaseHas('items', ['id' => $item->id, 'is_active' => false]);
    }
}
