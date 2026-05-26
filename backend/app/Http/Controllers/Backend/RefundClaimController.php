<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\RefundClaim;
use App\Models\RefundClaimMessage;
use App\Models\UserPayoutAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RefundClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = RefundClaim::with([
                'user:id,name,phone,email',
                'order:id,invoiceID,status,deliveryDate',
                'orderproduct:id,order_id,product_id,productName,productCode,quantity,productPrice,color,size',
                'product:id,ProductName,ProductSlug,ProductSku,ViewProductImage',
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('claim_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('order', function ($orderQuery) use ($search) {
                        $orderQuery->where('invoiceID', 'like', "%{$search}%");
                    })
                    ->orWhereHas('orderproduct', function ($lineQuery) use ($search) {
                        $lineQuery->where('productName', 'like', "%{$search}%");
                    });
            });
        }

        $claims = $query->paginate(30)->withQueryString();
        $payoutAccounts = $claims->getCollection()
            ->mapWithKeys(fn (RefundClaim $claim) => [
                $claim->id => $this->resellerAccount($claim->user_id),
            ]);

        return view('backend.content.refund.index', [
            'claims' => $claims,
            'payoutAccounts' => $payoutAccounts,
            'statuses' => RefundClaim::STATUSES,
        ]);
    }

    public function show(RefundClaim $claim)
    {
        $claim->load([
            'user:id,name,phone,email',
            'order:id,invoiceID,status,deliveryDate',
            'order.customers',
            'orderproduct:id,order_id,product_id,productName,productCode,quantity,productPrice,color,size',
            'product:id,ProductName,ProductSlug,ProductSku,ViewProductImage',
            'messages.user:id,name,phone,email',
            'messages.admin:id,name,email',
        ]);

        return view('backend.content.refund.show', [
            'claim' => $claim,
            'statuses' => RefundClaim::STATUSES,
            'payoutAccount' => $this->resellerAccount($claim->user_id),
        ]);
    }

    public function reply(Request $request, RefundClaim $claim)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $imageUrl = $request->hasFile('image')
            ? StorageHelper::store($request->file('image'), 'refund-claims')
            : null;

        RefundClaimMessage::create([
            'refund_claim_id' => $claim->id,
            'sender_type' => 'admin',
            'admin_id' => Auth::guard('admin')->id(),
            'message' => $data['message'],
            'attachment_path' => $imageUrl,
        ]);

        if ($claim->status === 'pending') {
            $claim->status = 'in_progress';
            $claim->save();
        }

        return redirect()
            ->route('admin.refunds.show', $claim)
            ->with('message', 'Reply sent successfully.');
    }

    public function updateStatus(Request $request, RefundClaim $claim)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(RefundClaim::STATUSES)],
        ]);

        $claim->status = $data['status'];
        $claim->save();

        return redirect()
            ->route('admin.refunds.show', $claim)
            ->with('message', 'Refund claim status updated.');
    }

    private function resellerAccount(?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        $account = UserPayoutAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->first();

        if ($account) {
            return [
                'label' => $account->provider_name ?: $account->bank_name ?: ucfirst(str_replace('_', ' ', $account->channel_type)),
                'name' => $account->account_name,
                'number' => $account->account_number,
                'source' => 'Payout account',
            ];
        }

        $bank = Bank::where('user_id', $userId)->latest()->first();
        if ($bank) {
            return [
                'label' => $bank->bank_name,
                'name' => $bank->account_name,
                'number' => $bank->account_number,
                'source' => 'Legacy bank info',
            ];
        }

        return null;
    }
}
