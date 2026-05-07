<?php

namespace App\Support;

class LineItemManager
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function normalise(array $items): array
    {
        return array_values(array_filter(array_map(function (array $item): ?array {
            $quantity = max(0.0, (float) ($item['quantity'] ?? 0));
            $unitPrice = max(0.0, (float) ($item['unit_price'] ?? 0));
            $costPrice = max(0.0, (float) ($item['cost_price'] ?? 0));
            $vatRate = max(0.0, (float) ($item['vat_rate'] ?? 0));

            if ($quantity <= 0 || blank($item['name'] ?? null)) {
                return null;
            }

            $subtotal = round($quantity * $unitPrice, 2);
            $taxTotal = round($subtotal * ($vatRate / 100), 2);
            $total = round($subtotal + $taxTotal, 2);

            return [
                'article_id' => isset($item['article_id']) && $item['article_id'] !== '' ? (int) $item['article_id'] : null,
                'supplier_entity_id' => isset($item['supplier_entity_id']) && $item['supplier_entity_id'] !== '' ? (int) $item['supplier_entity_id'] : null,
                'reference' => (string) ($item['reference'] ?? ''),
                'name' => (string) ($item['name'] ?? ''),
                'description' => (string) ($item['description'] ?? ''),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'cost_price' => $costPrice,
                'vat_rate' => $vatRate,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ];
        }, $items)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, float>
     */
    public static function totals(array $items): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $total = 0.0;
        $costTotal = 0.0;

        foreach ($items as $item) {
            $subtotal += (float) ($item['subtotal'] ?? 0);
            $taxTotal += (float) ($item['tax_total'] ?? 0);
            $total += (float) ($item['total'] ?? 0);
            $costTotal += round((float) ($item['quantity'] ?? 0) * (float) ($item['cost_price'] ?? 0), 2);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'cost_total' => round($costTotal, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function forSupplier(array $items, int $supplierId): array
    {
        return array_values(array_filter($items, fn (array $item): bool => (int) ($item['supplier_entity_id'] ?? 0) === $supplierId));
    }
}
