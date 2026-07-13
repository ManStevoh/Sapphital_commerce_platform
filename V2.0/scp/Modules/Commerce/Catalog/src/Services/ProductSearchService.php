<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Catalog\Models\ProductSearchQuery;
use Modules\Commerce\Catalog\Models\ProductSearchSynonym;

final class ProductSearchService
{
    /**
     * Built-in Nigerian English / marketplace synonym expansions (lowercase).
     *
     * @var array<string, list<string>>
     */
    private const PLATFORM_SYNONYMS = [
        'sneakers' => ['trainers', 'kicks'],
        'trainers' => ['sneakers', 'kicks'],
        'mobile' => ['phone', 'handset'],
        'phone' => ['mobile', 'handset'],
        'laundry' => ['washing'],
        'gas' => ['lpg', 'cooking gas'],
        'lpg' => ['gas', 'cooking gas'],
        'generator' => ['genset'],
        'genset' => ['generator'],
    ];

    /**
     * @param  array{
     *     q?: string|null,
     *     min_price_kobo?: int|null,
     *     max_price_kobo?: int|null,
     *     in_stock?: bool|null,
     *     fulfillment_type?: string|null,
     *     tag?: string|null,
     *     limit?: int
     * }  $filters
     * @return array{products: Collection<int, Product>, facets: array<string, mixed>, query: string, results_count: int}
     */
    public function search(string $tenantId, array $filters, bool $recordAnalytics = true): array
    {
        $queryText = trim((string) ($filters['q'] ?? ''));
        $limit = min(max((int) ($filters['limit'] ?? 24), 1), 50);

        $builder = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'published');

        $this->applyTextSearch($builder, $tenantId, $queryText);
        $this->applyFacets($builder, $filters);

        $products = (clone $builder)
            ->orderBy('name')
            ->limit($limit)
            ->get([
                'id',
                'tenant_id',
                'name',
                'slug',
                'price_kobo',
                'status',
                'inventory_qty',
                'fulfillment_type',
                'tags',
            ]);

        $facets = $this->buildFacets($tenantId, $queryText, $filters);

        if ($recordAnalytics && $queryText !== '') {
            ProductSearchQuery::query()->create([
                'tenant_id' => $tenantId,
                'query' => mb_substr($queryText, 0, 255),
                'results_count' => $products->count(),
                'searched_at' => now(),
            ]);
        }

        return [
            'products' => $products,
            'facets' => $facets,
            'query' => $queryText,
            'results_count' => $products->count(),
        ];
    }

    /**
     * @return list<string>
     */
    public function expandTerms(string $tenantId, string $queryText): array
    {
        $tokens = preg_split('/\s+/', mb_strtolower($queryText)) ?: [];
        $terms = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $terms[] = $token;

            foreach (self::PLATFORM_SYNONYMS[$token] ?? [] as $synonym) {
                $terms[] = $synonym;
            }

            $custom = ProductSearchSynonym::query()
                ->where('tenant_id', $tenantId)
                ->where('term', $token)
                ->pluck('synonym')
                ->all();

            foreach ($custom as $synonym) {
                if (is_string($synonym) && $synonym !== '') {
                    $terms[] = mb_strtolower($synonym);
                }
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * @param  Builder<Product>  $builder
     */
    private function applyTextSearch(Builder $builder, string $tenantId, string $queryText): void
    {
        if ($queryText === '') {
            return;
        }

        $terms = $this->expandTerms($tenantId, $queryText);

        $builder->where(function (Builder $outer) use ($terms, $queryText): void {
            $outer->where('name', 'like', '%'.$queryText.'%')
                ->orWhere('slug', 'like', '%'.$queryText.'%');

            foreach ($terms as $term) {
                $outer->orWhere('name', 'like', '%'.$term.'%')
                    ->orWhereJsonContains('tags', $term);
            }
        });
    }

    /**
     * @param  Builder<Product>  $builder
     * @param  array<string, mixed>  $filters
     */
    private function applyFacets(Builder $builder, array $filters): void
    {
        if (isset($filters['min_price_kobo']) && is_numeric($filters['min_price_kobo'])) {
            $builder->where('price_kobo', '>=', (int) $filters['min_price_kobo']);
        }

        if (isset($filters['max_price_kobo']) && is_numeric($filters['max_price_kobo'])) {
            $builder->where('price_kobo', '<=', (int) $filters['max_price_kobo']);
        }

        if (array_key_exists('in_stock', $filters) && $filters['in_stock'] !== null) {
            $inStock = filter_var($filters['in_stock'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($inStock === true) {
                $builder->where('inventory_qty', '>', 0);
            } elseif ($inStock === false) {
                $builder->where('inventory_qty', '<=', 0);
            }
        }

        if (isset($filters['fulfillment_type']) && is_string($filters['fulfillment_type']) && $filters['fulfillment_type'] !== '') {
            $builder->where('fulfillment_type', $filters['fulfillment_type']);
        }

        if (isset($filters['tag']) && is_string($filters['tag']) && $filters['tag'] !== '') {
            $builder->whereJsonContains('tags', $filters['tag']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildFacets(string $tenantId, string $queryText, array $filters): array
    {
        $base = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'published');

        $this->applyTextSearch($base, $tenantId, $queryText);

        // Facets ignore conflicting facet fields so counts stay navigable.
        $availability = (clone $base)
            ->selectRaw('SUM(CASE WHEN inventory_qty > 0 THEN 1 ELSE 0 END) as in_stock')
            ->selectRaw('SUM(CASE WHEN inventory_qty <= 0 THEN 1 ELSE 0 END) as out_of_stock')
            ->first();

        $types = (clone $base)
            ->selectRaw('fulfillment_type, COUNT(*) as aggregate')
            ->groupBy('fulfillment_type')
            ->pluck('aggregate', 'fulfillment_type')
            ->all();

        $price = (clone $base)
            ->selectRaw('MIN(price_kobo) as min_price_kobo, MAX(price_kobo) as max_price_kobo')
            ->first();

        return [
            'price' => [
                'min_kobo' => (int) ($price?->min_price_kobo ?? 0),
                'max_kobo' => (int) ($price?->max_price_kobo ?? 0),
                'applied_min_kobo' => isset($filters['min_price_kobo']) ? (int) $filters['min_price_kobo'] : null,
                'applied_max_kobo' => isset($filters['max_price_kobo']) ? (int) $filters['max_price_kobo'] : null,
            ],
            'availability' => [
                'in_stock' => (int) ($availability?->in_stock ?? 0),
                'out_of_stock' => (int) ($availability?->out_of_stock ?? 0),
            ],
            'fulfillment_type' => $types,
        ];
    }
}
