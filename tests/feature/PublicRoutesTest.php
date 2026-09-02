<?php

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class PublicRoutesTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    public function testHealthCheckReturns200(): void
    {
        $result = $this->get('healthz');

        $result->assertOK();
    }

    public function testHomePageReturns200(): void
    {
        $result = $this->get('/');

        $result->assertOK();
    }

    public function testProductsIndexReturns200(): void
    {
        $result = $this->get('products');

        $result->assertOK();
    }

    public function testSitemapReturns200(): void
    {
        $result = $this->get('sitemap.xml');

        $result->assertOK();
    }

    public function testRobotsTxtReturns200(): void
    {
        $result = $this->get('robots.txt');

        $result->assertOK();
    }

    public function testUnknownSlugReturns404(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->get('this-page-does-not-exist-anywhere');
    }

    public function testPublishedPageIsReachableAtItsSlug(): void
    {
        Database::connect()->table('pages')->insert([
            'title' => 'About Us', 'slug' => 'about-us-test', 'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->get('about-us-test');

        $result->assertOK();
    }

    public function testDraftPageIs404(): void
    {
        Database::connect()->table('pages')->insert([
            'title' => 'Draft Page', 'slug' => 'draft-page-test', 'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->expectException(PageNotFoundException::class);

        $this->get('draft-page-test');
    }
}
