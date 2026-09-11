<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductFeedController extends Controller
{
    public function google(): Response
    {
        $products = Product::feedReady()->with('category')->orderBy('id')->get();

        return response()->view('feeds.google-products', compact('products'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function meta(): StreamedResponse
    {
        $products = Product::feedReady()->with('category')->orderBy('id')->get();

        return response()->streamDownload(function () use ($products): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand', 'inventory', 'product_type'], ',', '"', '');
            foreach ($products as $product) {
                fputcsv($output, [
                    $this->csv($product->sku ?: (string) $product->id),
                    $this->csv($product->name),
                    $this->csv($product->excerpt ?: strip_tags((string) $product->description)),
                    str_replace('_', ' ', $product->availabilityForFeed()),
                    $product->condition,
                    number_format((float) $product->price, 2, '.', '').' SAR',
                    route('products.show', $product->slug),
                    $product->imageUrl(),
                    $this->csv($product->brand),
                    $product->track_stock ? $product->stock_quantity : 999,
                    $this->csv($product->category->name),
                ], ',', '"', '');
            }
            fclose($output);
        }, 'meta-products.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csv(?string $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
