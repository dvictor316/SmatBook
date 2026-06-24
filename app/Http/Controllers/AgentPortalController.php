<?php

namespace App\Http\Controllers;

use App\Models\AgentActivity;
use App\Models\AgentLead;
use App\Models\Company;
use App\Support\PartnerLocationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AgentPortalController extends Controller
{
    public function dashboard(): View
    {
        $user = Auth::user();
        $stats = $this->agentStats($user->id);
        $recentLeads = Schema::hasTable('agent_leads')
            ? $this->leadQuery($user->id)->latest()->limit(5)->get()
            : collect();
        $inactiveCustomers = $this->inactiveCustomers($user->id)->take(5);

        return view('agent.dashboard', compact('user', 'stats', 'recentLeads', 'inactiveCustomers'));
    }

    public function leads(Request $request): View
    {
        $user = Auth::user();
        if (!Schema::hasTable('agent_leads')) {
            $leads = new LengthAwarePaginator([], 0, 12);
            $stats = $this->agentStats($user->id);
            $categories = $this->leadCategories();
            $statuses = $this->leadStatuses();
            $sources = $this->leadSources();

            return view('agent.leads', compact('user', 'leads', 'stats', 'categories', 'statuses', 'sources'));
        }

        $query = $this->leadQuery($user->id);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($type = $request->query('type')) {
            if (in_array($type, ['personal', 'company', 'state_manager'], true)) {
                $query->where('lead_type', $type);
            }
        }

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        match ($request->query('sort')) {
            'oldest' => $query->oldest(),
            'activity' => $query->orderByDesc('last_activity_at')->latest(),
            default => $query->orderByRaw("FIELD(priority, 'hot', 'high', 'normal', 'low')")->latest(),
        };

        $leads = $query->paginate(12)->withQueryString();
        $stats = $this->agentStats($user->id);
        $categories = $this->leadCategories();
        $statuses = $this->leadStatuses();
        $sources = $this->leadSources();

        return view('agent.leads', compact('user', 'leads', 'stats', 'categories', 'statuses', 'sources'));
    }

    public function findNearby(): View
    {
        return view('agent.find-nearby', [
            'user' => Auth::user(),
            'categories' => $this->leadCategories(),
            'dailyLimit' => 7,
            'usedToday' => Schema::hasTable('agent_activities')
                ? AgentActivity::where('agent_id', Auth::id())->where('type', 'nearby_search')->whereDate('created_at', today())->count()
                : 0,
            'countryOptions' => PartnerLocationRepository::countryOptions(),
        ]);
    }

    public function storeLead(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:190'],
            'business_category' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'string', 'max:80'],
            'lead_type' => ['nullable', 'in:personal,company,state_manager'],
            'priority' => ['nullable', 'in:low,normal,high,hot'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if (!Schema::hasTable('agent_leads')) {
            return back()->with('error', 'Agent lead storage is not ready yet. Please run migrations first.');
        }

        $data['agent_id'] = Auth::id();
        $data['status'] = $data['status'] ?? 'new';
        $data['source'] = $data['source'] ?? 'manual';
        $data['lead_type'] = $data['lead_type'] ?? 'personal';
        $data['priority'] = $data['priority'] ?? 'normal';
        $data['last_activity_at'] = now();
        if (Schema::hasColumn('agent_leads', 'state_manager_id')) {
            $data['state_manager_id'] = Auth::user()?->state_manager_id;
        }

        $lead = AgentLead::create($data);

        $this->recordActivity($lead->id, 'system', 'Created as ' . str_replace('_', ' ', $lead->lead_type) . ' lead.', null);

        return back()->with('success', 'Lead added successfully.');
    }

    public function updateLead(Request $request, AgentLead $lead): RedirectResponse
    {
        $this->abortUnlessOwner($lead);

        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:80'],
            'priority' => ['nullable', 'in:low,normal,high,hot'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (($data['status'] ?? null) === 'converted' && !$lead->converted_at) {
            $data['converted_at'] = now();
        }

        $data['last_activity_at'] = now();
        $lead->update($data);
        $this->recordActivity($lead->id, 'system', 'Lead updated.', $data['notes'] ?? null);

        return back()->with('success', 'Lead updated.');
    }

    public function destroyLead(AgentLead $lead): RedirectResponse
    {
        $this->abortUnlessOwner($lead);
        $lead->delete();

        return back()->with('success', 'Lead deleted.');
    }

    public function performance(): View
    {
        $user = Auth::user();
        $stats = $this->agentStats($user->id);
        $heatmap = $this->activityHeatmap($user->id);

        return view('agent.performance', compact('user', 'stats', 'heatmap'));
    }

    public function earnings(): View
    {
        $user = Auth::user();
        $stats = $this->agentStats($user->id);
        $commissions = Schema::hasTable('deployment_commissions')
            ? DB::table('deployment_commissions')->where('manager_id', $user->id)->latest()->paginate(12)
            : collect();

        return view('agent.earnings', compact('user', 'stats', 'commissions'));
    }

    public function knowledgeBase(): View
    {
        return view('agent.knowledge-base', [
            'user' => Auth::user(),
            'modules' => [
                ['title' => 'Lead Playbook', 'body' => 'Qualification, follow-up rhythm, and closing cues for field agents.', 'tag' => 'Sales'],
                ['title' => 'Free Trial Guide', 'body' => 'How to position trials, set expectations, and convert after activation.', 'tag' => 'Trials'],
                ['title' => 'Find Nearby Workflow', 'body' => 'Use category searches and save strong prospects as leads.', 'tag' => 'Geo'],
            ],
        ]);
    }

    public function contentHub(): View
    {
        return view('agent.content-hub', [
            'user' => Auth::user(),
            'assets' => [
                ['title' => 'WhatsApp Pitch', 'type' => 'Message', 'accent' => 'green'],
                ['title' => 'SME Onboarding Flyer', 'type' => 'PDF', 'accent' => 'blue'],
                ['title' => 'Demo Checklist', 'type' => 'Checklist', 'accent' => 'amber'],
            ],
        ]);
    }

    private function leadQuery(int $agentId)
    {
        if (!Schema::hasTable('agent_leads')) {
            return AgentLead::query()->whereRaw('1 = 0');
        }

        return AgentLead::query()->where('agent_id', $agentId);
    }

    private function agentStats(int $agentId): array
    {
        $hasLeadTable = Schema::hasTable('agent_leads');
        $leadBase = $hasLeadTable ? $this->leadQuery($agentId) : null;
        $totalLeads = $hasLeadTable ? (clone $leadBase)->count() : 0;
        $convertedLeads = $hasLeadTable
            ? (clone $leadBase)
                ->where(function ($query) {
                    $query->whereNotNull('converted_at')->orWhere('status', 'converted');
                })
                ->count()
            : 0;
        $hotLeads = $hasLeadTable ? (clone $leadBase)->whereIn('status', ['interested', 'meeting_scheduled', 'negotiating', 'awaiting_payment'])->count() : 0;
        $newThisMonth = $hasLeadTable ? (clone $leadBase)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count() : 0;
        $totalBusinesses = $hasLeadTable ? (clone $leadBase)->whereIn('lead_type', ['company', 'state_manager'])->count() : 0;
        $leadConversion = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

        $companyIds = $this->agentCompanyIds($agentId);
        $subscriptionSummary = $this->subscriptionSummary($agentId, $companyIds);
        $commissionSummary = $this->commissionSummary($agentId);
        $activityCount = Schema::hasTable('agent_activities')
            ? AgentActivity::where('agent_id', $agentId)->where('created_at', '>=', now()->subDays(30))->count()
            : 0;

        $salesVolume = $subscriptionSummary['sales_volume'];
        $target = 1000000;
        $xp = min(1000, (int) ($totalLeads * 35 + $convertedLeads * 120 + floor($salesVolume / 1000) + $activityCount * 5));
        $rank = $xp >= 700 ? 'New Star' : ($xp >= 200 ? 'Starter' : '-');
        $score = min(100, round(($leadConversion * 0.45) + (($xp / 1000) * 35) + min(20, $activityCount), 1));
        $activeCustomers = max($convertedLeads, $subscriptionSummary['active']);
        $inactiveCustomers = max(0, $totalBusinesses + $convertedLeads - $activeCustomers);
        $customerTotal = max(1, $activeCustomers + $inactiveCustomers);

        return [
            'total_leads' => $totalLeads,
            'converted_leads' => $convertedLeads,
            'hot_leads' => $hotLeads,
            'total_businesses' => $totalBusinesses + count($companyIds),
            'new_businesses' => $newThisMonth,
            'free_trials' => $subscriptionSummary['trials'],
            'sales_volume' => $salesVolume,
            'target' => $target,
            'target_percent' => $target > 0 ? min(100, round(($salesVolume / $target) * 100)) : 0,
            'paid_commissions' => $commissionSummary['paid'],
            'pending_commissions' => $commissionSummary['pending'],
            'xp' => $xp,
            'rank' => $rank,
            'next_rank' => $xp >= 700 ? 'Pro Closer' : 'New Star',
            'lead_conversion' => $leadConversion,
            'retention' => round(($activeCustomers / $customerTotal) * 100),
            'churn' => round(($inactiveCustomers / $customerTotal) * 100),
            'active_customers' => $activeCustomers,
            'inactive_customers' => $inactiveCustomers,
            'performance_score' => $score,
            'activity_count' => $activityCount,
        ];
    }

    private function agentCompanyIds(int $agentId): array
    {
        $ids = collect();

        if (Schema::hasTable('deployment_companies') && Schema::hasColumn('deployment_companies', 'manager_id')) {
            $ids = $ids->merge(DB::table('deployment_companies')->where('manager_id', $agentId)->pluck('company_id'));
        }

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'deployed_by')) {
            $ids = $ids->merge(Company::query()->where('deployed_by', $agentId)->pluck('id'));
        }

        if (Schema::hasTable('agent_leads') && Schema::hasColumn('agent_leads', 'company_id')) {
            $ids = $ids->merge(AgentLead::query()->where('agent_id', $agentId)->whereNotNull('company_id')->pluck('company_id'));
        }

        return $ids->filter()->unique()->values()->map(fn ($id) => (int) $id)->all();
    }

    private function subscriptionSummary(int $agentId, array $companyIds): array
    {
        if (!Schema::hasTable('subscriptions')) {
            return ['trials' => 0, 'active' => 0, 'sales_volume' => 0.0];
        }

        $query = DB::table('subscriptions');
        $query->where(function ($q) use ($agentId, $companyIds) {
            if (Schema::hasColumn('subscriptions', 'deployed_by')) {
                $q->where('deployed_by', $agentId);
            }
            if ($companyIds && Schema::hasColumn('subscriptions', 'company_id')) {
                $q->orWhereIn('company_id', $companyIds);
            }
        });

        $rows = $query->get();

        return [
            'trials' => $rows->filter(fn ($row) => str_contains(strtolower((string) ($row->status ?? '')), 'trial'))->count(),
            'active' => $rows->filter(fn ($row) => in_array(strtolower((string) ($row->status ?? '')), ['active', 'paid'], true))->count(),
            'sales_volume' => (float) $rows->filter(fn ($row) => strtolower((string) ($row->payment_status ?? '')) === 'paid')->sum('amount'),
        ];
    }

    private function commissionSummary(int $agentId): array
    {
        if (!Schema::hasTable('deployment_commissions')) {
            return ['paid' => 0.0, 'pending' => 0.0];
        }

        $rows = DB::table('deployment_commissions')->where('manager_id', $agentId)->get();

        return [
            'paid' => (float) $rows->where('status', 'paid')->sum('amount'),
            'pending' => (float) $rows->where('status', 'pending')->sum('amount'),
        ];
    }

    private function inactiveCustomers(int $agentId): Collection
    {
        if (!Schema::hasTable('agent_leads')) {
            return collect();
        }

        return AgentLead::query()
            ->where('agent_id', $agentId)
            ->where(function ($query) {
                $query->whereNull('last_activity_at')->orWhere('last_activity_at', '<', now()->subDays(30));
            })
            ->latest()
            ->get();
    }

    private function activityHeatmap(int $agentId): array
    {
        $days = collect(range(41, 0))->map(function ($offset) use ($agentId) {
            $date = now()->subDays($offset);
            $count = Schema::hasTable('agent_activities')
                ? AgentActivity::where('agent_id', $agentId)->whereDate('created_at', $date->toDateString())->count()
                : 0;

            return ['date' => $date->format('M j'), 'count' => $count, 'level' => min(4, $count)];
        });

        return $days->values()->all();
    }

    private function recordActivity(int $leadId, string $type, string $title, ?string $body): void
    {
        if (!Schema::hasTable('agent_activities')) {
            return;
        }

        AgentActivity::create([
            'agent_id' => Auth::id(),
            'agent_lead_id' => $leadId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    private function abortUnlessOwner(AgentLead $lead): void
    {
        abort_unless((int) $lead->agent_id === (int) Auth::id(), 403);
    }

    private function leadCategories(): array
    {
        return ['Stores', 'Supermarkets', 'Pharmacies', 'Hospitals', 'Restaurants', 'Banks', 'Fuel Stations', 'Schools', 'Hotels', 'Salon', 'Electronics', 'Fashion', 'Education', 'Automotive', 'Real Estate'];
    }

    private function leadStatuses(): array
    {
        return ['new', 'interested', 'meeting_scheduled', 'demo_completed', 'negotiating', 'awaiting_payment', 'converted', 'lost'];
    }

    private function leadSources(): array
    {
        return ['social_media', 'referral', 'cold_call', 'walk_in', 'website', 'find_nearby', 'other'];
    }

}
