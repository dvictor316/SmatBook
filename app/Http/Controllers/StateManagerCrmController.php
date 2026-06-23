<?php

namespace App\Http\Controllers;

use App\Models\AgentLead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StateManagerCrmController extends Controller
{
    public function overview(): View
    {
        $manager = Auth::user();
        $agents = $this->agentsQuery()->get();
        $agentIds = $agents->pluck('id')->all();
        $stats = $this->managerStats($agentIds);
        $agentRows = $this->agentPerformanceRows($agents);
        $underperformingAgents = $agentRows->filter(fn ($row) => $row['sales'] <= 0 || $row['last_seen_days'] >= 30)->take(5);

        return view('deployment.crm.overview', compact('manager', 'agents', 'stats', 'agentRows', 'underperformingAgents'));
    }

    public function agents(Request $request): View
    {
        $query = $this->agentsQuery();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            if ($status !== 'all' && Schema::hasColumn('users', 'status')) {
                $query->where('status', $status);
            }
        }

        $agents = $query->latest()->paginate(12)->withQueryString();
        $agentRows = $this->agentPerformanceRows(collect($agents->items()));
        $zones = $this->zones();
        $stats = $this->managerStats($this->agentsQuery()->pluck('id')->all());

        return view('deployment.crm.agents', compact('agents', 'agentRows', 'zones', 'stats'));
    }

    public function leads(Request $request): View
    {
        $agents = $this->agentsQuery()->orderBy('name')->get();
        $agentIds = $agents->pluck('id')->all();
        $stats = $this->managerStats($agentIds);

        if (!Schema::hasTable('agent_leads')) {
            $leads = new LengthAwarePaginator([], 0, 12);
            return view('deployment.crm.leads', compact('agents', 'leads', 'stats'));
        }

        $query = AgentLead::query()->with('agent')->whereIn('agent_id', $agentIds);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->integer('agent_id'));
        }

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $leads = $query->latest()->paginate(12)->withQueryString();

        return view('deployment.crm.leads', compact('agents', 'leads', 'stats'));
    }

    public function inviteAgent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:60'],
            'zone_id' => ['nullable', 'integer'],
        ]);

        $password = Str::password(12);
        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'role' => 'agent',
        ];

        if (Schema::hasColumn('users', 'phone')) {
            $attributes['phone'] = $data['phone'] ?? null;
        }
        if (Schema::hasColumn('users', 'status')) {
            $attributes['status'] = 'active';
        }
        if (Schema::hasColumn('users', 'is_verified')) {
            $attributes['is_verified'] = 1;
        }
        if (Schema::hasColumn('users', 'email_verified_at')) {
            $attributes['email_verified_at'] = now();
        }

        $agent = User::updateOrCreate(['email' => $data['email']], $attributes);
        $this->assignAgentZone($agent->id, $data['zone_id'] ?? null);

        return back()->with('success', 'Agent invited. Temporary password: ' . $password);
    }

    public function assignZone(Request $request, User $agent): RedirectResponse
    {
        $request->validate(['zone_id' => ['nullable', 'integer']]);
        $this->assignAgentZone($agent->id, $request->integer('zone_id') ?: null);

        return back()->with('success', 'Agent zone updated.');
    }

    public function addViolation(Request $request, User $agent): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!Schema::hasTable('agent_violations')) {
            return back()->with('error', 'Violation storage is not ready. Please run migrations first.');
        }

        DB::table('agent_violations')->insert([
            'state_manager_id' => Auth::id(),
            'agent_id' => $agent->id,
            'title' => $data['title'],
            'severity' => $data['severity'],
            'notes' => $data['notes'] ?? null,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Violation recorded.');
    }

    public function suspendAgent(User $agent): RedirectResponse
    {
        abort_unless(strtolower((string) $agent->role) === 'agent', 403);

        if (Schema::hasColumn('users', 'status')) {
            $agent->forceFill(['status' => 'suspended'])->save();
        }

        return back()->with('success', 'Agent suspended.');
    }

    public function activateAgent(User $agent): RedirectResponse
    {
        abort_unless(strtolower((string) $agent->role) === 'agent', 403);

        if (Schema::hasColumn('users', 'status')) {
            $agent->forceFill(['status' => 'active'])->save();
        }

        return back()->with('success', 'Agent activated.');
    }

    public function reports(Request $request): View
    {
        $agents = $this->agentsQuery()->get();
        $agentIds = $agents->pluck('id')->all();
        $stats = $this->managerStats($agentIds);
        $agentRows = $this->agentPerformanceRows($agents);
        $zones = $this->zonePerformance($agentRows);
        $hotLeads = $this->hotLeads($agentIds);

        return view('deployment.crm.reports', compact('agents', 'agentRows', 'zones', 'hotLeads', 'stats'));
    }

    private function agentsQuery()
    {
        $query = User::query()->whereRaw("LOWER(COALESCE(role, '')) = 'agent'");

        if (Schema::hasTable('agent_zone_assignments')) {
            $assignedIds = DB::table('agent_zone_assignments')
                ->where('state_manager_id', Auth::id())
                ->pluck('agent_id')
                ->all();

            if (!empty($assignedIds)) {
                $query->whereIn('id', $assignedIds);
            }
        }

        return $query;
    }

    private function managerStats(array $agentIds): array
    {
        $totalAgents = count($agentIds);
        $activeAgents = Schema::hasColumn('users', 'status')
            ? $this->agentsQuery()->where('status', 'active')->count()
            : $totalAgents;
        $leadCount = Schema::hasTable('agent_leads') ? AgentLead::whereIn('agent_id', $agentIds)->count() : 0;
        $converted = Schema::hasTable('agent_leads') ? AgentLead::whereIn('agent_id', $agentIds)->where(function ($q) {
            $q->where('status', 'converted')->orWhereNotNull('converted_at');
        })->count() : 0;
        $freeTrials = Schema::hasTable('subscriptions')
            ? DB::table('subscriptions')->whereIn('deployed_by', $agentIds)->whereRaw("LOWER(COALESCE(status, '')) LIKE '%trial%'")->count()
            : 0;
        $stateRevenue = Schema::hasTable('subscriptions')
            ? (float) DB::table('subscriptions')->whereIn('deployed_by', $agentIds)->whereRaw("LOWER(COALESCE(payment_status, '')) = 'paid'")->sum('amount')
            : 0.0;
        $agentsWithSales = $this->agentsWithSales($agentIds);
        $activeCustomers = max($converted, $agentsWithSales);
        $inactiveCustomers = max(0, $leadCount - $activeCustomers);
        $customerTotal = max(1, $activeCustomers + $inactiveCustomers);
        $revenueTarget = 1044000000;
        $customerTarget = 6264;

        return [
            'total_agents' => $totalAgents,
            'active_agents' => $activeAgents,
            'new_agents' => $this->agentsQuery()->where('created_at', '>=', now()->startOfMonth())->count(),
            'total_businesses' => $leadCount,
            'new_businesses' => Schema::hasTable('agent_leads') ? AgentLead::whereIn('agent_id', $agentIds)->where('created_at', '>=', now()->startOfMonth())->count() : 0,
            'state_revenue' => $stateRevenue,
            'free_trials' => $freeTrials,
            'agent_activation_rate' => $totalAgents > 0 ? round(($agentsWithSales / $totalAgents) * 100) : 0,
            'retention' => round(($activeCustomers / $customerTotal) * 100),
            'churn' => round(($inactiveCustomers / $customerTotal) * 100),
            'active_customers' => $activeCustomers,
            'inactive_customers' => $inactiveCustomers,
            'revenue_target' => $revenueTarget,
            'revenue_percent' => $revenueTarget > 0 ? min(100, round(($stateRevenue / $revenueTarget) * 100, 1)) : 0,
            'customer_target' => $customerTarget,
            'customer_percent' => $customerTarget > 0 ? min(100, round(($leadCount / $customerTarget) * 100, 1)) : 0,
            'days_left' => max(0, now()->endOfYear()->diffInDays(now())),
        ];
    }

    private function agentPerformanceRows($agents)
    {
        $zonesByAgent = $this->zonesByAgent();

        return $agents->map(function ($agent) use ($zonesByAgent) {
            $leadQuery = Schema::hasTable('agent_leads') ? AgentLead::where('agent_id', $agent->id) : null;
            $leads = $leadQuery ? (clone $leadQuery)->count() : 0;
            $converted = $leadQuery ? (clone $leadQuery)->where(function ($q) {
                $q->where('status', 'converted')->orWhereNotNull('converted_at');
            })->count() : 0;
            $sales = Schema::hasTable('subscriptions') ? (float) DB::table('subscriptions')->where('deployed_by', $agent->id)->whereRaw("LOWER(COALESCE(payment_status, '')) = 'paid'")->sum('amount') : 0.0;
            $violations = Schema::hasTable('agent_violations') ? DB::table('agent_violations')->where('agent_id', $agent->id)->where('status', 'open')->count() : 0;
            $lastActivity = Schema::hasTable('agent_activities') ? DB::table('agent_activities')->where('agent_id', $agent->id)->max('created_at') : null;
            $lastSeenDays = $lastActivity ? now()->diffInDays($lastActivity) : 999;
            $performance = min(100, (int) (($converted * 20) + min(50, $sales / 10000) + max(0, 30 - $violations * 10)));

            return [
                'agent' => $agent,
                'zone' => $zonesByAgent[$agent->id] ?? '-',
                'leads' => $leads,
                'converted' => $converted,
                'sales' => $sales,
                'clients' => $converted . ' Active / ' . max($leads, $converted) . ' Total',
                'violations' => $violations,
                'performance' => $performance,
                'last_seen_days' => $lastSeenDays,
                'status' => Schema::hasColumn('users', 'status') ? ($agent->status ?? 'active') : 'active',
            ];
        });
    }

    private function zones()
    {
        if (!Schema::hasTable('state_manager_zones')) {
            return collect();
        }

        if (!DB::table('state_manager_zones')->where('state_manager_id', Auth::id())->exists()) {
            foreach (['AMAC', 'BWARI', 'KUJE', 'GWAGWALADA', 'ABAJI', 'KWALI'] as $zone) {
                DB::table('state_manager_zones')->insert([
                    'state_manager_id' => Auth::id(),
                    'name' => $zone,
                    'code' => Str::slug($zone),
                    'target_revenue' => 0,
                    'target_customers' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return DB::table('state_manager_zones')->where('state_manager_id', Auth::id())->where('is_active', true)->orderBy('name')->get();
    }

    private function zonesByAgent(): array
    {
        if (!Schema::hasTable('agent_zone_assignments') || !Schema::hasTable('state_manager_zones')) {
            return [];
        }

        return DB::table('agent_zone_assignments')
            ->leftJoin('state_manager_zones', 'agent_zone_assignments.zone_id', '=', 'state_manager_zones.id')
            ->where('agent_zone_assignments.state_manager_id', Auth::id())
            ->pluck('state_manager_zones.name', 'agent_zone_assignments.agent_id')
            ->toArray();
    }

    private function assignAgentZone(int $agentId, ?int $zoneId): void
    {
        if (!Schema::hasTable('agent_zone_assignments')) {
            return;
        }

        DB::table('agent_zone_assignments')->updateOrInsert(
            ['state_manager_id' => Auth::id(), 'agent_id' => $agentId],
            ['zone_id' => $zoneId, 'assigned_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function agentsWithSales(array $agentIds): int
    {
        if (!$agentIds || !Schema::hasTable('subscriptions')) {
            return 0;
        }

        return DB::table('subscriptions')
            ->whereIn('deployed_by', $agentIds)
            ->whereRaw("LOWER(COALESCE(payment_status, '')) = 'paid'")
            ->distinct('deployed_by')
            ->count('deployed_by');
    }

    private function zonePerformance($agentRows)
    {
        return $agentRows->groupBy('zone')->map(function ($rows, $zone) {
            return [
                'zone' => $zone ?: '-',
                'agents' => $rows->count(),
                'revenue' => $rows->sum('sales'),
                'leads' => $rows->sum('leads'),
            ];
        })->values();
    }

    private function hotLeads(array $agentIds)
    {
        if (!Schema::hasTable('agent_leads')) {
            return collect();
        }

        return AgentLead::query()
            ->with('agent')
            ->whereIn('agent_id', $agentIds)
            ->whereIn('status', ['interested', 'meeting_scheduled', 'negotiating', 'awaiting_payment'])
            ->latest()
            ->limit(10)
            ->get();
    }
}
