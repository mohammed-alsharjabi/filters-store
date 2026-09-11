<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadOrderReceiptRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OrderReceiptController extends Controller
{
    public function store(UploadOrderReceiptRequest $request, string $token): RedirectResponse
    {
        $order = Order::query()->where('public_token', $token)->firstOrFail();
        abort_if($order->status === 'cancelled', 422, 'لا يمكن رفع سند لطلب ملغي.');

        $file = $request->file('receipt');
        $path = $file->store('order-receipts/'.$order->id, 'local');
        abort_unless($path, 500, 'تعذر حفظ سند التحويل.');
        $originalName = preg_replace('/[^\pL\pN._ -]/u', '', basename($file->getClientOriginalName())) ?: 'receipt';

        try {
            $oldPath = DB::transaction(function () use ($order, $file, $path, $originalName): ?string {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
                abort_if($lockedOrder->status === 'cancelled', 422, 'لا يمكن رفع سند لطلب ملغي.');
                $oldPath = $lockedOrder->receipt_path;
                $lockedOrder->update([
                    'receipt_path' => $path,
                    'receipt_original_name' => mb_substr($originalName, 0, 255),
                    'receipt_mime_type' => $file->getMimeType(),
                    'receipt_size' => $file->getSize(),
                    'receipt_uploaded_at' => now(),
                    'status' => $lockedOrder->status === 'pending_transfer' ? 'payment_review' : $lockedOrder->status,
                ]);

                return $oldPath;
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'تم رفع سند التحويل وإرساله للمراجعة بنجاح.');
    }
}
