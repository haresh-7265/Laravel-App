<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ImportProductRowJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(
        public readonly array $row
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $row = $this->row;

        // ── Price validation ──────────────────────────────────────────
        $price = (float) ($row['price'] ?? 0);

        if ($price < 0) {
            $this->fail(new \InvalidArgumentException(
                "Row [{$row['name']}]: price cannot be negative ({$price})."
            ));
            return;
        }

        $discountPrice = !empty($row['discount_price']) ? (float) $row['discount_price'] : null;

        if ($discountPrice !== null && $discountPrice < 0) {
            $this->fail(new \InvalidArgumentException(
                "Row [{$row['name']}]: discount_price cannot be negative ({$discountPrice})."
            ));
            return;
        }

        if ($discountPrice !== null && $discountPrice >= $price) {
            $this->fail(new \InvalidArgumentException(
                "Row [{$row['name']}]: discount_price ({$discountPrice}) must be lower than price ({$price})."
            ));
            return;
        }

        // ── Category — find or create by name ────────────────────────
        $categoryName = trim($row['category'] ?? '');

        if (empty($categoryName)) {
            $this->fail(new \InvalidArgumentException(
                "Row [{$row['name']}]: category name is required."
            ));
            return;
        }

        $category = Category::firstOrCreate(
            ['name' => $categoryName],
            ['name' => str($categoryName)->lower()]
        );

        // ── Tags validation ───────────────────────────────────────────
        // Valid format: comma-separated string e.g. "summer,sale,new"
        // or already a JSON array string e.g. '["summer","sale"]'
        $tags = $this->parseTags($row['tags'] ?? null);

        // ── Upsert product ────────────────────────────────────────────
        $slug = !empty($row['slug'])
            ? $row['slug']
            : str($row['name'] ?? 'untitled-' . uniqid())->slug();

        Product::updateOrCreate(
            ['slug' => $slug],
            [
                'name'           => $row['name'] ?? 'Untitled',
                'slug'           => $slug,
                'price'          => $price,
                'discount_price' => $discountPrice,
                'stock'          => max(0, (int) ($row['stock'] ?? 0)),
                'description'    => $row['description'] ?? null,
                'category_id'    => $category->id,
                'is_active'      => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'tags'           => $tags,
            ]
        );
    }

    /**
     * Parse tags from CSV string or JSON string.
     * Returns array or null if invalid/empty.
     *
     * Accepts:
     *   "summer,sale,new"       → ['summer', 'sale', 'new']
     *   '["summer","sale"]'     → ['summer', 'sale']
     *   ""  / null / "123,,"   → null
     */
    private function parseTags(mixed $raw): ?array
    {
        if (empty($raw)) {
            return null;
        }

        $raw = trim($raw);

        // Try JSON array first
        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tags = array_values(array_filter(array_map('trim', $decoded)));
                return !empty($tags) ? $tags : null;
            }
            return null; // invalid JSON → discard
        }

        // Comma-separated string
        // Each tag: letters, numbers, hyphens only — anything else = invalid tag (skipped)
        $tags = array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            fn ($tag) => $tag !== '' && preg_match('/^[\w\-]+$/u', $tag)
        ));

        return !empty($tags) ? $tags : null;
    }
}