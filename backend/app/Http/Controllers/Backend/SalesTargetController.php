<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SalesTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalesTargetController extends Controller
{
    public function index()
    {
        $targets = SalesTarget::orderByDesc('priority')
            ->orderByDesc('id')
            ->paginate(20);

        return view('backend.content.sales_target.index', compact('targets'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = optional(Auth::guard('admin')->user())->id;

        SalesTarget::create($data);

        return redirect()
            ->route('admin.sales-targets.index')
            ->with('message', 'Sales target created successfully.');
    }

    public function update(Request $request, $id)
    {
        $target = SalesTarget::findOrFail($id);
        $target->update($this->validatedData($request));

        return redirect()
            ->route('admin.sales-targets.index')
            ->with('message', 'Sales target updated successfully.');
    }

    public function destroy($id)
    {
        $target = SalesTarget::findOrFail($id);
        $target->delete();

        return redirect()
            ->route('admin.sales-targets.index')
            ->with('message', 'Sales target removed successfully.');
    }

    public function toggleStatus($id)
    {
        $target = SalesTarget::findOrFail($id);
        $target->status = $target->status === 'Active' ? 'Inactive' : 'Active';
        $target->save();

        return redirect()
            ->route('admin.sales-targets.index')
            ->with('message', 'Sales target status updated successfully.');
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_type' => ['required', Rule::in(['quantity', 'amount'])],
            'target_value' => ['required', 'numeric', 'min:1'],
            'order_scope' => ['required', Rule::in(['non_canceled', 'delivered'])],
            'reward_type' => ['required', Rule::in(['bonus', 'gift', 'reward'])],
            'reward_value' => ['nullable', 'numeric', 'min:0'],
            'reward_note' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);
    }
}
