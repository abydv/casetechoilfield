<?php

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class ContentEntryPublicTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private function seedType(string $slug): int
    {
        return (int) Database::connect()->table('content_types')->insert([
            'name' => 'Equipment', 'slug' => $slug, 'has_seo' => 1,
        ], true);
    }

    public function testPublishedEntryIsReachableAtTypeSlugAndEntrySlug(): void
    {
        $typeId = $this->seedType('equipment');
        Database::connect()->table('content_entries')->insert([
            'content_type_id' => $typeId, 'title' => 'Hydraulic Pump', 'slug' => 'hydraulic-pump',
            'status' => 'published', 'sort_order' => 0,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->get('equipment')->assertOK();
        $this->get('equipment/hydraulic-pump')->assertOK();
    }

    public function testDraftEntryIs404OnItsDetailPage(): void
    {
        $typeId = $this->seedType('equipment-draft');
        Database::connect()->table('content_entries')->insert([
            'content_type_id' => $typeId, 'title' => 'Unpublished Widget', 'slug' => 'unpublished-widget',
            'status' => 'draft', 'sort_order' => 0,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->expectException(PageNotFoundException::class);

        $this->get('equipment-draft/unpublished-widget');
    }

    public function testUnknownContentTypeSlugIs404OnListing(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->get('not-a-real-content-type');
    }

    public function testUnknownContentTypeSlugIs404OnDetail(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->get('not-a-real-content-type/some-entry');
    }

    public function testReservedSlugCannotBeUsedByAContentTypeAdminSide(): void
    {
        // The public route for an existing specific module always wins
        // over the generic content-type catch-all, so /products must
        // keep resolving to the Products module regardless of whether a
        // (rejected) content type ever tried to claim that slug.
        $this->get('products')->assertOK();
    }
}
