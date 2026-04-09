<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catSkin = Category::query()->firstOrCreate(
            ['name' => 'عناية بالبشرة'],
            ['description' => 'مرطبات ومنظفات', 'is_active' => true]
        );
        $catMakeup = Category::query()->firstOrCreate(
            ['name' => 'مكياج'],
            ['description' => 'ألوان وأساسات', 'is_active' => true]
        );
        $catPerfume = Category::query()->firstOrCreate(
            ['name' => 'عطور'],
            ['description' => 'عطور ومستحضرات', 'is_active' => true]
        );

        $supA = Supplier::query()->firstOrCreate(
            ['name' => 'مورد التجميل الأول'],
            ['phone' => '0500000001', 'address' => 'الرياض']
        );
        $supB = Supplier::query()->firstOrCreate(
            ['name' => 'شركة العناية'],
            ['phone' => '0500000002', 'address' => 'جدة']
        );

        $items = [
            [
                'name' => 'مرطب فيتامين سي',
                'barcode' => '6281000000001',
                'category_id' => $catSkin->id,
                'supplier_id' => $supA->id,
                'sale_price' => 45.5,
                'min_stock_alert' => 15,
                'description' => 'مرطب يومي 50 مل',
                'batches' => [
                    ['code' => 'VC-2026-A', 'days_exp' => 400, 'qty' => 40, 'rem' => 35, 'cost' => 22],
                    ['code' => 'VC-2026-B', 'days_exp' => 120, 'qty' => 25, 'rem' => 25, 'cost' => 21],
                ],
            ],
            [
                'name' => 'غسول وجه لطيف',
                'barcode' => '6281000000002',
                'category_id' => $catSkin->id,
                'supplier_id' => $supB->id,
                'sale_price' => 32,
                'min_stock_alert' => 10,
                'description' => 'مناسب للبشرة الحساسة',
                'batches' => [
                    ['code' => 'FW-01', 'days_exp' => 25, 'qty' => 18, 'rem' => 12, 'cost' => 14],
                ],
            ],
            [
                'name' => 'أحمر شفاه مطفي — وردي',
                'barcode' => '6281000000003',
                'category_id' => $catMakeup->id,
                'supplier_id' => $supA->id,
                'sale_price' => 59.9,
                'min_stock_alert' => 8,
                'description' => null,
                'batches' => [
                    ['code' => 'ML-P01', 'days_exp' => 500, 'qty' => 50, 'rem' => 48, 'cost' => 28],
                ],
            ],
            [
                'name' => 'ماسكارا مقاومة للماء',
                'barcode' => '6281000000004',
                'category_id' => $catMakeup->id,
                'supplier_id' => $supA->id,
                'sale_price' => 72,
                'min_stock_alert' => 5,
                'description' => null,
                'batches' => [
                    ['code' => 'MS-BLK', 'days_exp' => 200, 'qty' => 30, 'rem' => 4, 'cost' => 35],
                ],
            ],
            [
                'name' => 'عطر زهري 100 مل',
                'barcode' => '6281000000005',
                'category_id' => $catPerfume->id,
                'supplier_id' => $supB->id,
                'sale_price' => 189,
                'min_stock_alert' => 6,
                'description' => 'إصدار محدود',
                'batches' => [
                    ['code' => 'PF-FL-1', 'days_exp' => 600, 'qty' => 12, 'rem' => 10, 'cost' => 95],
                    ['code' => 'PF-FL-2', 'days_exp' => 45, 'qty' => 8, 'rem' => 8, 'cost' => 92],
                ],
            ],
            [
                'name' => 'سيروم حمض الهيالورونيك',
                'barcode' => '6281000000006',
                'category_id' => $catSkin->id,
                'supplier_id' => $supB->id,
                'sale_price' => 95,
                'min_stock_alert' => 20,
                'description' => '30 مل',
                'batches' => [
                    ['code' => 'HY-24', 'days_exp' => 300, 'qty' => 60, 'rem' => 60, 'cost' => 48],
                ],
            ],
        ];

        foreach ($items as $row) {
            $batches = $row['batches'];
            unset($row['batches']);

            $product = Product::query()->updateOrCreate(
                ['barcode' => $row['barcode']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'category_id' => $row['category_id'],
                    'supplier_id' => $row['supplier_id'],
                    'sale_price' => $row['sale_price'],
                    'min_stock_alert' => $row['min_stock_alert'],
                    'is_active' => true,
                ]
            );

            foreach ($batches as $b) {
                ProductBatch::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'batch_code' => $b['code'],
                    ],
                    [
                        'expiration_date' => now()->addDays($b['days_exp'])->toDateString(),
                        'quantity' => $b['qty'],
                        'remaining_quantity' => $b['rem'],
                        'cost_price' => $b['cost'],
                    ]
                );
            }
        }
    }
}
