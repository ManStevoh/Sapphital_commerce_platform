<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Commerce\Catalog\Enums\CollectionType;
use Modules\Commerce\Catalog\Models\Collection;
use Modules\Commerce\Catalog\Models\CollectionProduct;
use Modules\Commerce\Catalog\Models\Product;

final class CollectionProductResolver
{
    private const MAX_LIMIT = 50;

    /**
     * @return SupportCollection<int, Product>
     */
    public function resolve(Collection $collection, int $limit = 24, ?string $cursor = null): SupportCollection
    {
        $limit = min(max($limit, 1), self::MAX_LIMIT);

        $query = $collection->type === CollectionType::Manual
            ? $this->manualQuery($collection)
            : $this->smartQuery($collection);

        $this->applySort($query, $collection);

        if ($cursor !== null && $cursor !== '') {
            $query->where('products.id', '>', $cursor);
        }

        return $query
            ->limit($limit)
            ->get([
                'products.id',
                'products.tenant_id',
                'products.name',
                'products.slug',
                'products.price_kobo',
                'products.status',
                'products.inventory_qty',
                'products.fulfillment_type',
                'products.tags',
            ]);
    }

    /**
     * @return Builder<Product>
     */
    private function manualQuery(Collection $collection): Builder
    {
        $productIds = CollectionProduct::query()
            ->where('collection_id', $collection->id)
            ->where('tenant_id', $collection->tenant_id)
            ->orderBy('position')
            ->pluck('product_id')
            ->all();

        $query = Product::query()
            ->where('tenant_id', $collection->tenant_id)
            ->where('status', 'published');

        if ($productIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereIn('id', $productIds)
            ->orderByRaw('CASE products.id '.collect($productIds)->map(
                static fn (string $id, int $index): string => "WHEN '{$id}' THEN {$index}"
            )->implode(' ').' ELSE 9999 END');
    }

    /**
     * @return Builder<Product>
     */
    private function smartQuery(Collection $collection): Builder
    {
        $rules = is_array($collection->rules_json) ? $collection->rules_json : [];
        $preset = is_string($rules['preset'] ?? null) ? $rules['preset'] : null;

        $query = Product::query()
            ->where('products.tenant_id', $collection->tenant_id)
            ->where('products.status', 'published');

        return match ($preset) {
            'new_arrivals' => $this->applyNewArrivals($query, $rules),
            'on_sale' => $query->whereJsonContains('products.tags', 'sale'),
            'best_sellers' => $this->applyBestSellers($query, $collection->tenant_id, $rules),
            default => $this->applyRuleList($query, $rules),
        };
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $rules
     * @return Builder<Product>
     */
    private function applyNewArrivals(Builder $query, array $rules): Builder
    {
        $days = max(1, min((int) ($rules['days'] ?? 30), 365));

        return $query->where('products.created_at', '>=', now()->subDays($days));
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $rules
     * @return Builder<Product>
     */
    private function applyBestSellers(Builder $query, string $tenantId, array $rules): Builder
    {
        $days = max(1, min((int) ($rules['days'] ?? 30), 365));

        if (! Schema::hasTable('order_items') || ! Schema::hasTable('orders')) {
            return $query->orderByDesc('products.created_at');
        }

        $since = now()->subDays($days)->toDateTimeString();

        $ranked = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.tenant_id', $tenantId)
            ->whereIn('orders.status', ['paid', 'fulfilled'])
            ->where('orders.created_at', '>=', $since)
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as units_sold'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('units_sold')
            ->limit(200)
            ->pluck('product_id')
            ->filter()
            ->values()
            ->all();

        if ($ranked === []) {
            return $query->whereRaw('1 = 0');
        }

        $orderSql = collect($ranked)->map(
            static fn (string $id, int $index): string => "WHEN '{$id}' THEN {$index}"
        )->implode(' ');

        return $query
            ->whereIn('products.id', $ranked)
            ->orderByRaw("CASE products.id {$orderSql} ELSE 9999 END");
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $rulesJson
     * @return Builder<Product>
     */
    private function applyRuleList(Builder $query, array $rulesJson): Builder
    {
        $rules = $rulesJson['rules'] ?? [];

        if (! is_array($rules) || $rules === []) {
            return $query;
        }

        foreach (array_slice($rules, 0, 10) as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $field = (string) ($rule['field'] ?? '');
            $operator = (string) ($rule['operator'] ?? 'eq');
            $value = $rule['value'] ?? null;

            match ($field) {
                'tag' => $this->applyTagRule($query, $operator, $value),
                'type', 'fulfillment_type' => $this->applyFulfillmentRule($query, $operator, $value),
                'price_kobo', 'price_cents' => $this->applyPriceRule($query, $operator, $value),
                'created_at' => $this->applyCreatedAtRule($query, $operator, $value),
                default => null,
            };
        }

        return $query;
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyTagRule(Builder $query, string $operator, mixed $value): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (in_array($operator, ['eq', 'contains'], true)) {
            $query->whereJsonContains('products.tags', $value);
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyFulfillmentRule(Builder $query, string $operator, mixed $value): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if ($operator === 'eq') {
            $query->where('products.fulfillment_type', $value);
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyPriceRule(Builder $query, string $operator, mixed $value): void
    {
        if (! is_numeric($value)) {
            return;
        }

        $amount = (int) $value;

        match ($operator) {
            'lte', 'lt' => $query->where('products.price_kobo', $operator === 'lt' ? '<' : '<=', $amount),
            'gte', 'gt' => $query->where('products.price_kobo', $operator === 'gt' ? '>' : '>=', $amount),
            'eq' => $query->where('products.price_kobo', $amount),
            default => null,
        };
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyCreatedAtRule(Builder $query, string $operator, mixed $value): void
    {
        if (is_numeric($value) && in_array($operator, ['gte', 'gt'], true)) {
            $query->where('products.created_at', '>=', now()->subDays(max(1, (int) $value)));

            return;
        }

        if (! is_string($value) || $value === '') {
            return;
        }

        try {
            $when = Carbon::parse($value);
        } catch (\Throwable) {
            return;
        }

        match ($operator) {
            'gte', 'gt' => $query->where('products.created_at', $operator === 'gt' ? '>' : '>=', $when),
            'lte', 'lt' => $query->where('products.created_at', $operator === 'lt' ? '<' : '<=', $when),
            default => null,
        };
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, Collection $collection): void
    {
        if ($collection->type === CollectionType::Manual && $collection->sort_order === 'manual') {
            return;
        }

        if ($collection->type === CollectionType::Smart) {
            $preset = is_array($collection->rules_json) ? ($collection->rules_json['preset'] ?? null) : null;
            if ($preset === 'best_sellers') {
                return;
            }
        }

        match ($collection->sort_order) {
            'newest' => $query->orderByDesc('products.created_at'),
            'price_asc' => $query->orderBy('products.price_kobo'),
            'price_desc' => $query->orderByDesc('products.price_kobo'),
            'best_selling' => $query->orderByDesc('products.created_at'),
            default => $query->orderBy('products.name'),
        };
    }
}
