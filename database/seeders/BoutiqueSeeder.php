<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BoutiqueSeeder extends Seeder
{
    public function run(): void
    {
        // Create owner
        $user = User::create([
            'name' => 'Amara Okafor',
            'email' => 'amara@luxeboutique.com',
            'password' => Hash::make('password'),
        ]);

        // Create store
        $store = Store::create([
            'user_id' => $user->id,
            'name' => 'Luxe Amara',
            'slug' => 'luxe-amara',
            'whatsapp_number' => '08012345678',
            'description' => 'Premium fashion for the modern African woman.',
        ]);

        // Products data
        $products = [
            [
                'name' => 'Ankara Wrap Dress',
                'description' => 'A stunning wrap dress crafted from premium Ankara fabric. Perfect for occasions that demand elegance.',
                'price' => 35000,
                'category' => 'Dresses',
                'variants' => [
                    ['size' => 'S', 'quantity' => 4],
                    ['size' => 'M', 'quantity' => 6],
                    ['size' => 'L', 'quantity' => 3],
                    ['size' => 'XL', 'quantity' => 2],
                ],
            ],
            [
                'name' => 'Lace Bodycon Dress',
                'description' => 'Sultry lace bodycon dress that hugs every curve. Available in midnight black.',
                'price' => 28000,
                'category' => 'Dresses',
                'variants' => [
                    ['size' => 'XS', 'quantity' => 2],
                    ['size' => 'S', 'quantity' => 5],
                    ['size' => 'M', 'quantity' => 4],
                    ['size' => 'L', 'quantity' => 0],
                ],
            ],
            [
                'name' => 'Silk Slip Dress',
                'description' => 'Effortlessly chic silk slip dress. Wear it out or layer it for any occasion.',
                'price' => 22000,
                'category' => 'Dresses',
                'variants' => [
                    ['size' => 'S', 'quantity' => 3],
                    ['size' => 'M', 'quantity' => 5],
                    ['size' => 'L', 'quantity' => 4],
                ],
            ],
            [
                'name' => 'Blazer & Trouser Set',
                'description' => 'Power dressing redefined. This matching blazer and trouser set means business.',
                'price' => 55000,
                'category' => 'Sets',
                'variants' => [
                    ['size' => 'S', 'quantity' => 2],
                    ['size' => 'M', 'quantity' => 3],
                    ['size' => 'L', 'quantity' => 2],
                    ['size' => 'XL', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Crop Top & Skirt Set',
                'description' => 'Trendy two-piece set with a fitted crop top and flared midi skirt. A true head-turner.',
                'price' => 32000,
                'category' => 'Sets',
                'variants' => [
                    ['size' => 'XS', 'quantity' => 3],
                    ['size' => 'S', 'quantity' => 4],
                    ['size' => 'M', 'quantity' => 4],
                    ['size' => 'L', 'quantity' => 2],
                ],
            ],
            [
                'name' => 'Satin Corset Top',
                'description' => 'Luxurious satin corset top that pairs perfectly with trousers, skirts or jeans.',
                'price' => 18000,
                'category' => 'Tops',
                'variants' => [
                    ['size' => 'S', 'quantity' => 6],
                    ['size' => 'M', 'quantity' => 5],
                    ['size' => 'L', 'quantity' => 3],
                ],
            ],
            [
                'name' => 'Peplum Blouse',
                'description' => 'Elegant peplum blouse in breathable chiffon. Office-ready and weekend-worthy.',
                'price' => 15000,
                'category' => 'Tops',
                'variants' => [
                    ['size' => 'S', 'quantity' => 4],
                    ['size' => 'M', 'quantity' => 6],
                    ['size' => 'L', 'quantity' => 4],
                    ['size' => 'XL', 'quantity' => 2],
                ],
            ],
            [
                'name' => 'Wide Leg Trousers',
                'description' => 'Flowing wide leg trousers in premium fabric. Effortless style for any setting.',
                'price' => 24000,
                'category' => 'Bottoms',
                'variants' => [
                    ['size' => 'S', 'quantity' => 3],
                    ['size' => 'M', 'quantity' => 5],
                    ['size' => 'L', 'quantity' => 4],
                    ['size' => 'XL', 'quantity' => 3],
                ],
            ],
            [
                'name' => 'Midi Pencil Skirt',
                'description' => 'Classic midi pencil skirt in structured fabric. The cornerstone of every wardrobe.',
                'price' => 19000,
                'category' => 'Bottoms',
                'variants' => [
                    ['size' => 'XS', 'quantity' => 2],
                    ['size' => 'S', 'quantity' => 4],
                    ['size' => 'M', 'quantity' => 5],
                    ['size' => 'L', 'quantity' => 0],
                    ['size' => 'XL', 'quantity' => 0],
                ],
            ],
            [
                'name' => 'Kaftan Dress',
                'description' => 'Flowing kaftan dress with intricate embroidery. Comfort and elegance in perfect harmony.',
                'price' => 42000,
                'category' => 'Dresses',
                'variants' => [
                    ['size' => 'S', 'quantity' => 3],
                    ['size' => 'M', 'quantity' => 4],
                    ['size' => 'L', 'quantity' => 3],
                    ['size' => 'XL', 'quantity' => 2],
                ],
            ],
            [
                'name' => 'Sequin Mini Dress',
                'description' => 'Turn heads in this dazzling sequin mini dress. Made for nights that matter.',
                'price' => 38000,
                'category' => 'Dresses',
                'variants' => [
                    ['size' => 'XS', 'quantity' => 1],
                    ['size' => 'S', 'quantity' => 3],
                    ['size' => 'M', 'quantity' => 2],
                    ['size' => 'L', 'quantity' => 0],
                ],
            ],
            [
                'name' => 'Denim Jacket',
                'description' => 'Classic denim jacket with a modern fit. The ultimate layering piece.',
                'price' => 27000,
                'category' => 'Outerwear',
                'variants' => [
                    ['size' => 'S', 'quantity' => 4],
                    ['size' => 'M', 'quantity' => 5],
                    ['size' => 'L', 'quantity' => 3],
                    ['size' => 'XL', 'quantity' => 2],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $variants = $productData['variants'];
            unset($productData['variants']);

            $product = Product::create([
                ...$productData,
                'store_id' => $store->id,
                'is_published' => true,
                'images' => [],
            ]);

            foreach ($variants as $variant) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $variant['size'],
                    'quantity' => $variant['quantity'],
                ]);
            }
        }

        // Sample orders
        $firstProduct = Product::where('store_id', $store->id)->first();
        $firstVariant = $firstProduct->variants()->first();

        $order = Order::create([
            'store_id' => $store->id,
            'customer_name' => 'Chioma Eze',
            'customer_email' => 'chioma@gmail.com',
            'customer_phone' => '08098765432',
            'total_amount' => 35000,
            'payment_method' => 'paystack',
            'status' => 'delivered',
            'paystack_reference' => 'PAY_TEST_001',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $firstVariant->id,
            'product_name' => $firstProduct->name,
            'size' => $firstVariant->size,
            'quantity' => 1,
            'unit_price' => 35000,
        ]);

        $order2 = Order::create([
            'store_id' => $store->id,
            'customer_name' => 'Fatima Bello',
            'customer_phone' => '08055544433',
            'total_amount' => 46000,
            'payment_method' => 'whatsapp',
            'status' => 'confirmed',
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_variant_id' => $firstVariant->id,
            'product_name' => $firstProduct->name,
            'size' => $firstVariant->size,
            'quantity' => 1,
            'unit_price' => 35000,
        ]);
    }
}