<?php

namespace Tests\Feature;

use Illuminate\Foundation\Vite;
use Tests\Concerns\UsesGearDataset;
use Tests\TestCase;

class CdnCachingTest extends TestCase
{
    use UsesGearDataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->useGearDataset();
    }

    public function test_pages_are_cacheable_on_the_cdn_and_revalidated_by_browsers(): void
    {
        $response = $this->get(route('statistics.index'))
            ->assertOk()
            ->assertHeader('Vercel-CDN-Cache-Control', 'max-age=31536000')
            ->assertHeaderMissing('Set-Cookie');

        $this->assertSame('max-age=0, must-revalidate, public', $response->headers->get('Cache-Control'));

        // Vite skips font preloads it already rendered, which only happens when one app serves several requests.
        app(Vite::class)->flush();

        $this->get(route('statistics.index'), ['If-None-Match' => $response->headers->get('ETag')])
            ->assertNotModified()
            ->assertHeader('Vercel-CDN-Cache-Control');
    }

    public function test_redirects_and_errors_are_not_cached(): void
    {
        $this->get(route('gear.index', ['slot' => 'tabard']))
            ->assertRedirect()
            ->assertHeaderMissing('Vercel-CDN-Cache-Control');

        $this->get(route('gear.class', ['class' => 'bard']))
            ->assertNotFound()
            ->assertHeaderMissing('Vercel-CDN-Cache-Control');
    }
}
