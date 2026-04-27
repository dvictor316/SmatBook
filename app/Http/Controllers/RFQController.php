<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RequestForQuotation;
use App\Models\RfqSupplier;
use App\Models\RfqItem;

class RFQController extends Controller
{
    /**
     * Get the active branch context (id, name) from session.
     *
     * @return array
     */
    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id', auth()->user()->branch_id ?? null),
            'name' => session('active_branch_name', null),
        ];
    }
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $rfqs = RequestForQuotation::where('company_id', $companyId)
            ->with(['rfqSuppliers'])
            ->latest()
            ->paginate(20);

        return view('rfq.index', compact('rfqs'));
    }

    public function create()
    {
        return view('rfq.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'required_date'   => 'nullable|date',
            'notes'           => 'nullable|string',
            'items'           => 'required|array|min:1',
            'items.*.product_id'    => 'nullable|exists:products,id',
            'items.*.product_name'  => 'required|string|max:255',
            'items.*.quantity'      => 'required|numeric|min:0.0001',
            'items.*.unit'          => 'nullable|string|max:50',
            'items.*.specifications' => 'nullable|string',
        ]);

        $user = auth()->user();


        $branch = $this->getActiveBranchContext();
        $rfq = RequestForQuotation::create([
            'company_id'    => $user->company_id,
            'branch_id'     => $branch['id'],
            'branch_name'   => $branch['name'],
            'rfq_number'    => $this->generateRfqNumber($user->company_id),
            'title'         => $validated['title'],
            'required_date' => $validated['required_date'] ?? null,
            'notes'         => $validated['notes'] ?? null,
            'status'        => 'draft',
            'created_by'    => $user->id,
        ]);

        foreach ($validated['items'] as $item) {
            $rfq->items()->create($item);
        }

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'RFQ created successfully.');
    }

    public function show(RequestForQuotation $rfq)
    {
        $this->authorizeCompany($rfq);
        $rfq->load(['items.product', 'rfqSuppliers']);

        return view('rfq.show', compact('rfq'));
    }

    public function edit(RequestForQuotation $rfq)
    {
        $this->authorizeCompany($rfq);
        abort_if($rfq->status !== 'draft', 403, 'Only draft RFQs can be edited.');
        $rfq->load(['items', 'rfqSuppliers']);

        return view('rfq.edit', compact('rfq'));
    }

    public function update(Request $request, RequestForQuotation $rfq)
    {
        $this->authorizeCompany($rfq);
        abort_if($rfq->status !== 'draft', 403, 'Only draft RFQs can be edited.');

        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'required_date' => 'nullable|date',
            'notes'         => 'nullable|string',
        ]);

        $rfq->update($validated);

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'RFQ updated successfully.');
    }

    public function destroy(RequestForQuotation $rfq)
    {
        $this->authorizeCompany($rfq);
        abort_if($rfq->status === 'sent', 403, 'Cannot delete a sent RFQ.');
        $rfq->delete();

        return redirect()->route('rfq.index')
            ->with('success', 'RFQ deleted.');
    }

    // ── Suppliers ──────────────────────────────────────────────────────────

    public function addSupplier(Request $request, RequestForQuotation $rfq)
    {
        $this->authorizeCompany($rfq);
        $validated = $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'supplier_name'  => 'required|string|max:255',
            'supplier_email' => 'nullable|email|max:255',
        ]);

        $rfq->rfqSuppliers()->firstOrCreate(
            ['supplier_id' => $validated['supplier_id']],
            $validated + ['status' => 'pending']
        );

        return back()->with('success', 'Supplier added to RFQ.');
    }

    public function removeSupplier(RequestForQuotation $rfq, RfqSupplier $rfqSupplier)
    {
        $this->authorizeCompany($rfq);
        $rfqSupplier->delete();

        return back()->with('success', 'Supplier removed.');
    }

    public function sendToSuppliers(Request $request, RequestForQuotation $rfq)
    {
        $this->authorizeCompany($rfq);
        abort_if($rfq->rfqSuppliers()->count() === 0, 422, 'Add at least one supplier before sending.');

        $rfq->rfqSuppliers()->whereNull('sent_at')->update([
            'sent_at' => now(),
            'status'  => 'sent',
        ]);

        $rfq->update(['status' => 'sent']);

        return back()->with('success', 'RFQ sent to all suppliers.');
    }

    // ── Quotes ─────────────────────────────────────────────────────────────

    public function receiveQuote(Request $request, RequestForQuotation $rfq, RfqSupplier $rfqSupplier)
    {
        $this->authorizeCompany($rfq);
        $validated = $request->validate([
            'quoted_amount' => 'required|numeric|min:0',
            'currency'      => 'nullable|string|max:10',
            'validity_date' => 'nullable|date',
            'notes'         => 'nullable|string',
        ]);

        $rfqSupplier->update($validated + [
            'status'     => 'quoted',
            'quoted_at'  => now(),
        ]);

        $rfq->update(['status' => 'received']);

        return back()->with('success', 'Quote recorded.');
    }

    public function compareQuotes(RequestForQuotation $rfq)
    {
        $this->authorizeCompany($rfq);
        $rfq->load(['items.product', 'rfqSuppliers']);

        return view('rfq.compare', compact('rfq'));
    }

    public function selectWinner(Request $request, RequestForQuotation $rfq)
    {
        $this->authorizeCompany($rfq);
        $validated = $request->validate([
            'rfq_supplier_id' => 'required|exists:rfq_suppliers,id',
        ]);

        $rfq->rfqSuppliers()->update(['is_winner' => false]);
        RfqSupplier::where('id', $validated['rfq_supplier_id'])
            ->where('rfq_id', $rfq->id)
            ->update(['is_winner' => true]);

        $rfq->update(['status' => 'awarded']);

        return back()->with('success', 'Winning supplier selected. You can now convert to a Purchase Order.');
    }

    // ── Private ────────────────────────────────────────────────────────────

    private function authorizeCompany(RequestForQuotation $rfq): void
    {
        abort_if($rfq->company_id !== auth()->user()->company_id, 403);
    }

    private function generateRfqNumber(int $companyId): string
    {
        $count = RequestForQuotation::where('company_id', $companyId)->count() + 1;
        return 'RFQ-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
