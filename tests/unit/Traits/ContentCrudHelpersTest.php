<?php

use App\Traits\ContentCrudHelpers;
use Config\Database;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class ContentCrudHelpersTest extends DatabaseTestCase
{
    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class {
            use ContentCrudHelpers;

            public function slugifyPublic(string $value): string
            {
                return $this->slugify($value);
            }

            public function uniqueSlugPublic(string $table, string $base, ?int $excludeId = null): string
            {
                return $this->uniqueSlug($table, $base, $excludeId);
            }
        };
    }

    public function testSlugifyLowercasesAndDasherizes(): void
    {
        $this->assertSame('hydraulic-cementing-pump', $this->subject->slugifyPublic('Hydraulic Cementing Pump'));
    }

    public function testSlugifyStripsPunctuationAndCollapsesSeparators(): void
    {
        $this->assertSame('weight-kg', $this->subject->slugifyPublic('Weight (kg)'));
    }

    public function testSlugifyFallsBackToItemWhenNothingAlphanumericRemains(): void
    {
        $this->assertSame('item', $this->subject->slugifyPublic('***'));
    }

    public function testUniqueSlugReturnsBaseWhenNotTaken(): void
    {
        $slug = $this->subject->uniqueSlugPublic('content_types', 'Brand New Type');

        $this->assertSame('brand-new-type', $slug);
    }

    public function testUniqueSlugAppendsSuffixOnCollision(): void
    {
        Database::connect()->table('content_types')->insert([
            'name' => 'Equipment', 'slug' => 'equipment', 'has_seo' => 1,
        ]);

        $slug = $this->subject->uniqueSlugPublic('content_types', 'Equipment');

        $this->assertSame('equipment-2', $slug);
    }

    public function testUniqueSlugExcludesGivenIdSoEditingARecordKeepsItsOwnSlug(): void
    {
        $id = Database::connect()->table('content_types')->insert([
            'name' => 'Equipment', 'slug' => 'equipment', 'has_seo' => 1,
        ], true);

        $slug = $this->subject->uniqueSlugPublic('content_types', 'Equipment', (int) $id);

        $this->assertSame('equipment', $slug);
    }
}
