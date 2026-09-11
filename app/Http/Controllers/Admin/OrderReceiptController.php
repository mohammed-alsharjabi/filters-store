<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderReceiptController extends Controller
{
    public function __invoke(Order $order): StreamedResponse
    {
        Gate::authorize('view-leads');
        abort_unless($order->receipt_path && Storage::disk('local')->exists($order->receipt_path), 404);

        return Storage::disk('local')->download(
            $order->receipt_path,
            $order->receipt_original_name ?: 'receipt-'.$order->order_number,
            ['Content-Type' => $order->receipt_mime_type ?: 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff']
        );
    }
}
