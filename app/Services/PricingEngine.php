<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;

/**
 * Two prices, one rule: wholesale customers get the wholesale price.
 * Quantity tiers stay out until a shop actually asks for them.
 */
class PricingEngine
{
    public function priceFor(Product $product, string $type = 'retail'): int
    {
        return $type === 'wholesale' && $product->wholesale_price > 0
            ? (int) $product->wholesale_price
            : (int) $product->retail_price;
    }

    public function typeFor(?Customer $customer): string
    {
        return $customer?->is_wholesale
            ? 'wholesale'
            : (string) settings('sale.default_type', 'retail');
    }

    /** The floor a discount may not cross. Zero means no floor was set. */
    public function floorFor(Product $product): int
    {
        return (int) $product->min_price;
    }
}
