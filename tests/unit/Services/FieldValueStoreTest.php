<?php

use App\Services\FieldValueStore;
use Config\Database;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class FieldValueStoreTest extends DatabaseTestCase
{
    private FieldValueStore $store;
    private int $entryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new FieldValueStore();

        $db = Database::connect();
        $typeId = $db->table('content_types')->insert([
            'name' => 'Equipment', 'slug' => 'equipment-' . uniqid(), 'has_seo' => 1,
        ], true);
        $this->entryId = (int) $db->table('content_entries')->insert([
            'content_type_id' => $typeId, 'title' => 'Test Entry', 'slug' => 'test-entry-' . uniqid(),
            'status' => 'draft', 'sort_order' => 0,
        ], true);
    }

    private function makeField(string $key, string $type, int $typeId = 0): array
    {
        $db = Database::connect();
        $contentTypeId = $db->table('content_entries')->select('content_type_id')->where('id', $this->entryId)->get()->getRowArray()['content_type_id'];
        $id = $db->table('custom_fields')->insert([
            'content_type_id' => $contentTypeId, 'field_key' => $key, 'label' => ucfirst($key),
            'field_type' => $type, 'sort_order' => 0, 'is_required' => 0,
        ], true);

        return ['id' => $id, 'field_key' => $key, 'field_type' => $type];
    }

    public function testTextFieldRoundTrips(): void
    {
        $field = $this->makeField('manufacturer', 'text');
        $this->store->setValue($this->entryId, $field, 'CaseTech');

        $this->assertSame('CaseTech', $this->store->getByFieldKey($this->entryId, [$field])['manufacturer']);
    }

    public function testNumberFieldRoundTrips(): void
    {
        $field = $this->makeField('weight', 'number');
        $this->store->setValue($this->entryId, $field, '450.5');

        // Compared as a float rather than a fixed string: MySQL's
        // DECIMAL(18,4) always returns a zero-padded string ("450.5000"),
        // SQLite3 doesn't enforce that scale and returns "450.5" — both
        // are the correct value, just formatted differently per driver.
        $value = $this->store->getByFieldKey($this->entryId, [$field])['weight'];
        $this->assertEqualsWithDelta(450.5, (float) $value, 0.0001);
    }

    public function testDateFieldRoundTrips(): void
    {
        $field = $this->makeField('available_from', 'date');
        $this->store->setValue($this->entryId, $field, '2026-01-15');

        // value_date is a DATETIME column (docs/database-schema.md §3):
        // MySQL always returns it zero-padded to a full timestamp
        // ("2026-01-15 00:00:00"), SQLite3 returns exactly what was
        // stored ("2026-01-15") — both are the same date, just formatted
        // per the driver's own type affinity.
        $value = $this->store->getByFieldKey($this->entryId, [$field])['available_from'];
        $this->assertStringStartsWith('2026-01-15', $value);
    }

    public function testCheckboxFieldRoundTrips(): void
    {
        $field = $this->makeField('in_stock', 'checkbox');
        $this->store->setValue($this->entryId, $field, '1');

        $this->assertSame('1', $this->store->getByFieldKey($this->entryId, [$field])['in_stock']);
    }

    public function testMultiselectFieldRoundTripsAsArray(): void
    {
        $field = $this->makeField('tags', 'multiselect');
        $this->store->setValue($this->entryId, $field, ['New', 'Popular']);

        $this->assertSame(['New', 'Popular'], $this->store->getByFieldKey($this->entryId, [$field])['tags']);
    }

    public function testMultiselectFieldIsNullWhenNeverSet(): void
    {
        // No custom_field_values row exists at all yet — distinct from a
        // row that exists but was cleared (see the "cleared" tests below,
        // which do decode to their type's empty default).
        $field = $this->makeField('tags2', 'multiselect');

        $this->assertNull($this->store->getByFieldKey($this->entryId, [$field])['tags2']);
    }

    public function testMultiselectFieldClearedByEmptyArrayStaysEmptyArray(): void
    {
        $field = $this->makeField('tags3', 'multiselect');
        $this->store->setValue($this->entryId, $field, ['New']);
        $this->store->setValue($this->entryId, $field, []);

        $this->assertSame([], $this->store->getByFieldKey($this->entryId, [$field])['tags3']);
    }

    public function testImageFieldStoresMediaIdAsInt(): void
    {
        $field = $this->makeField('photo', 'image');
        $this->store->setValue($this->entryId, $field, '42');

        $this->assertSame(42, $this->store->getByFieldKey($this->entryId, [$field])['photo']);
    }

    public function testRichtextFieldIsNullWhenNeverSet(): void
    {
        $field = $this->makeField('description', 'richtext');

        $this->assertNull($this->store->getByFieldKey($this->entryId, [$field])['description']);
    }

    public function testRichtextFieldRoundTripsAsScalarString(): void
    {
        $field = $this->makeField('description2', 'richtext');
        $this->store->setValue($this->entryId, $field, 'A high pressure hydraulic pump.');

        $value = $this->store->getByFieldKey($this->entryId, [$field])['description2'];

        $this->assertSame('A high pressure hydraulic pump.', $value);
    }

    /**
     * Regression test: FieldValueStore used to lump textarea/richtext in
     * with the array-valued JSON types (multiselect/gallery/repeater), so
     * a field that was saved and then cleared decoded back to [] instead
     * of '' — which crashed the admin edit form's textarea rendering with
     * "Array to string conversion" (see app/Views/admin/content_entries/form.php).
     */
    public function testRichtextFieldClearedByNullValueStaysEmptyString(): void
    {
        $field = $this->makeField('description3', 'richtext');
        $this->store->setValue($this->entryId, $field, 'Some text');
        $this->store->setValue($this->entryId, $field, null);

        $value = $this->store->getByFieldKey($this->entryId, [$field])['description3'];

        $this->assertSame('', $value);
    }

    public function testTextareaFieldIsNullWhenNeverSet(): void
    {
        $field = $this->makeField('short_note', 'textarea');

        $this->assertNull($this->store->getByFieldKey($this->entryId, [$field])['short_note']);
    }

    public function testTextareaFieldClearedByNullValueStaysEmptyString(): void
    {
        $field = $this->makeField('short_note2', 'textarea');
        $this->store->setValue($this->entryId, $field, 'Some note');
        $this->store->setValue($this->entryId, $field, null);

        $this->assertSame('', $this->store->getByFieldKey($this->entryId, [$field])['short_note2']);
    }

    public function testUnsetFieldReturnsNullForScalarTypes(): void
    {
        $field = $this->makeField('unset_text', 'text');

        $this->assertNull($this->store->getByFieldKey($this->entryId, [$field])['unset_text']);
    }
}
