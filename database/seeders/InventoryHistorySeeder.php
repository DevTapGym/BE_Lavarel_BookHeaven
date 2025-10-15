<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\Order;
use App\Models\ImportReceipt;
use App\Models\InventoryHistory;

class InventoryHistorySeeder extends Seeder
{
    public function run(): void
    {
        $book = Book::first();
        if (!$book) {
            return; // need at least one book
        }

        $order = Order::first();
        $receipt = ImportReceipt::first();

        $rows = [
            [
                'book_id' => $book->id,
                'order_id' => null,
                'import_receipt_id' => $receipt->id ?? null,
                'type' => 'IMPORT',
                'qty_stock_before' => 100,
                'qty_change' => 50,
                'qty_stock_after' => 150,
                'price' => 50000,
                'total_price' => 2500000,
                'transaction_date' => now()->subDays(7),
                'description' => 'Initial stock import',
            ],
            [
                'book_id' => $book->id,
                'order_id' => $order->id ?? null,
                'import_receipt_id' => null,
                'type' => 'SALE',
                'qty_stock_before' => 150,
                'qty_change' => -3,
                'qty_stock_after' => 147,
                'price' => 75000,
                'total_price' => 225000,
                'transaction_date' => now()->subDays(1),
                'description' => 'Order sale deduction',
            ],
        ];

        foreach ($rows as $data) {
            InventoryHistory::create($data);
        }
    }
}


