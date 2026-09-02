<?php

use App\Models\ContentEntryModel;
use App\Models\ContentTypeModel;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class ContentTypeAndEntryModelTest extends DatabaseTestCase
{
    public function testFindBySlugReturnsTheMatchingType(): void
    {
        $model = new ContentTypeModel();
        $model->insert(['name' => 'Equipment', 'slug' => 'equipment', 'has_seo' => 1]);

        $found = $model->findBySlug('equipment');

        $this->assertNotNull($found);
        $this->assertSame('Equipment', $found['name']);
    }

    public function testFindBySlugReturnsNullWhenNoMatch(): void
    {
        $model = new ContentTypeModel();

        $this->assertNull($model->findBySlug('does-not-exist'));
    }

    public function testPublishedQueryOnlyReturnsPublishedEntries(): void
    {
        $typeModel = new ContentTypeModel();
        $typeId = $typeModel->insert(['name' => 'Equipment', 'slug' => 'equipment', 'has_seo' => 1], true);

        $entryModel = new ContentEntryModel();
        $entryModel->insert(['content_type_id' => $typeId, 'title' => 'Published One', 'slug' => 'published-one', 'status' => 'published', 'sort_order' => 0]);
        $entryModel->insert(['content_type_id' => $typeId, 'title' => 'Draft One', 'slug' => 'draft-one', 'status' => 'draft', 'sort_order' => 0]);

        $published = (new ContentEntryModel())->publishedQuery((int) $typeId)->findAll();

        $this->assertCount(1, $published);
        $this->assertSame('Published One', $published[0]['title']);
    }

    public function testFindBySlugIsScopedToItsContentType(): void
    {
        $typeModel = new ContentTypeModel();
        $typeA = $typeModel->insert(['name' => 'Equipment', 'slug' => 'equipment', 'has_seo' => 1], true);
        $typeB = $typeModel->insert(['name' => 'Documents', 'slug' => 'documents', 'has_seo' => 1], true);

        $entryModel = new ContentEntryModel();
        $entryModel->insert(['content_type_id' => $typeA, 'title' => 'Shared Slug', 'slug' => 'shared-slug', 'status' => 'published', 'sort_order' => 0]);

        $this->assertNotNull($entryModel->findBySlug((int) $typeA, 'shared-slug'));
        $this->assertNull($entryModel->findBySlug((int) $typeB, 'shared-slug'));
    }
}
