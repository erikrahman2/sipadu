<?php

namespace Database\Seeders;

use App\Models\Feed;
use Illuminate\Database\Seeder;

class FeedSeeder extends Seeder
{
    /**
     * Seed the feeds table with initial configurations.
     *
     * These feeds target the news listing pages:
     * - PA Painan: https://pa-painan.go.id/category/berita-terbaru/ (WordPress)
     * - Disdukcapil: https://disdukcapil.pesisirselatankab.go.id/news (custom CMS)
     *
     * NOTE: CSS selectors must match the target site's HTML structure.
     * Run `php artisan feeds:sync` to actually fetch content.
     */
    public function run(): void
    {
        $feeds = [
            [
                'name'                 => 'PA Painan - Berita Terbaru',
                'url'                  => 'https://pa-painan.go.id/category/berita-terbaru/',
                'source_type'          => 'official_site',
                'title_selector'       => 'h2.entry-title a, h3.post-title a, h2 a, h3 a, .post-title a, .entry-title a',
                'link_selector'        => 'h2.entry-title a, h3.post-title a, h2 a, h3 a, .post-title a, .entry-title a',
                'date_selector'        => 'time, .entry-date, .post-date, .published, span[itemprop="datePublished"]',
                'excerpt_selector'     => '.entry-summary, .post-excerpt, .excerpt, .post-meta + p',
                'category_selector'    => '.category-links a, .post-category, .entry-categories a',
                'content_selector'     => null,
                'sync_interval_minutes'=> 120, // Sync every 2 hours
                'is_active'            => true,
            ],
            [
                'name'                 => 'Disdukcapil Pessel - News',
                'url'                  => 'https://disdukcapil.pesisirselatankab.go.id/news',
                'source_type'          => 'official_site',
                'title_selector'       => 'h2 a, h3 a, h4 a, .card-title a, .news-title a, .title a, .item-title a',
                'link_selector'        => 'h2 a, h3 a, h4 a, .card-title a, .news-title a, .title a, .item-title a',
                'date_selector'        => 'time, .date, .post-date, .published, .news-date',
                'excerpt_selector'     => '.card-text, .news-excerpt, .excerpt, .description, p',
                'category_selector'    => null,
                'content_selector'     => null,
                'sync_interval_minutes'=> 180, // Sync every 3 hours
                'is_active'            => true,
            ],
        ];

        foreach ($feeds as $feedConfig) {
            Feed::firstOrCreate(
                ['url' => $feedConfig['url']],
                $feedConfig
            );
        }

        $this->command->info('2 feeds configured successfully.');
    }
}
