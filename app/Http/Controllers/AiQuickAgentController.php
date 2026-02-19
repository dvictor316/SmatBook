<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AiQuickAgentController extends Controller
{
    public function query(Request $request): JsonResponse
    {
        try {
            $messageInput = (string) ($request->input('message', $request->query('message', '')));
            $messageInput = trim($messageInput);
            if ($messageInput === '') {
                return response()->json([
                    'ok' => true,
                    'answer' => "Please enter a question. Example: total sales today.",
                ]);
            }

            $message = $this->normalizeMessage($messageInput);
            $period = $this->resolvePeriod($message);
            $intent = $this->resolveIntent($message);
            $userPlan = $this->getCurrentPlan($request);
            $modelRouting = $this->resolveWithModel($messageInput);

            if (!empty($modelRouting['intent'])) {
                $intent = $this->intentFromType((string) $modelRouting['intent']) ?? $intent;
            }
            if (!empty($modelRouting['period'])) {
                $period = $this->resolvePeriod((string) $modelRouting['period']);
            }

            if (!$intent) {
                $general = $this->answerGeneralQuestion($messageInput, $request);
                $this->appendConversation($request, $messageInput, $general);

                return response()->json([
                    'ok' => true,
                    'answer' => $general,
                ]);
            }

            if (!$this->hasPlanAccess($userPlan, $intent['required_plan'], $request)) {
                $required = ucfirst($intent['required_plan'] === 'professional' ? 'Professional (Pro)' : $intent['required_plan']);
                $current = ucfirst($userPlan);
                $answer = "This AI query requires {$required} plan. You are on {$current}. Please upgrade to continue.";
                $this->appendConversation($request, $messageInput, $answer);

                return response()->json([
                    'ok' => true,
                    'restricted' => true,
                    'answer' => $answer,
                ]);
            }

            $response = $this->runIntent($request, $intent['type'], $period);
            $answer = (string) data_get($response->getData(true), 'answer', '');
            if ($answer !== '') {
                $this->appendConversation($request, $messageInput, $answer);
            }
            return $response;
        } catch (\Throwable $e) {
            Log::error('AI Quick Agent failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => optional($request->user())->id,
                'message' => $request->input('message', $request->query('message')),
            ]);

            return response()->json([
                'ok' => false,
                'answer' => 'AI task failed. Please try again.',
            ], 500);
        }
    }

    private function normalizeMessage(string $message): string
    {
        $normalized = strtolower(trim($message));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        // Common shorthand / typos from live usage
        $replacements = [
            'ue today' => 'invoices due today',
            'inv due' => 'invoice due',
            'pls' => 'please',
            'u ' => 'you ',
            'ur ' => 'your ',
            'p/l' => 'profit loss',
        ];
        $normalized = str_replace(array_keys($replacements), array_values($replacements), $normalized);

        if (str_starts_with($normalized, 'ue ')) {
            $normalized = 'invoices due ' . trim(substr($normalized, 3));
        }

        return $normalized;
    }

    private function resolveIntent(string $message): ?array
    {
        $message = strtolower(preg_replace('/\s+/', ' ', trim($message)));

        // Conversational / direct prompts
        if (
            str_contains($message, 'hello') ||
            str_contains($message, 'hi ') ||
            str_contains($message, 'hey') ||
            str_contains($message, 'who are you')
        ) {
            return ['type' => 'assistant_intro', 'required_plan' => 'basic'];
        }

        if (
            str_contains($message, 'help') ||
            str_contains($message, 'what can you do') ||
            str_contains($message, 'how do you work')
        ) {
            return ['type' => 'assistant_help', 'required_plan' => 'basic'];
        }

        if (
            str_contains($message, 'revenue') ||
            str_contains($message, 'turnover') ||
            str_contains($message, 'income from sales') ||
            str_contains($message, 'how much did i make') ||
            str_contains($message, 'how much did we make') ||
            str_contains($message, 'how much sold') ||
            str_contains($message, 'how much did i sell') ||
            str_contains($message, 'how much did we sell') ||
            str_contains($message, 'sell ') ||
            str_contains($message, 'sold ')
        ) {
            return ['type' => 'sales', 'required_plan' => 'basic'];
        }

        if (str_contains($message, 'all sales') || str_contains($message, 'sales report') || str_contains($message, 'all sale')) {
            return ['type' => 'sales', 'required_plan' => 'basic'];
        }

        if (str_contains($message, 'profit') || str_contains($message, 'p/l') || str_contains($message, 'loss')) {
            return ['type' => 'profit_loss', 'required_plan' => 'professional'];
        }

        if (str_contains($message, 'general ledger') || str_contains($message, 'ledger')) {
            return ['type' => 'general_ledger', 'required_plan' => 'enterprise'];
        }

        if (str_contains($message, 'trial balance')) {
            return ['type' => 'trial_balance', 'required_plan' => 'enterprise'];
        }

        if (str_contains($message, 'balance sheet')) {
            return ['type' => 'balance_sheet', 'required_plan' => 'enterprise'];
        }

        if (str_contains($message, 'tax') || str_contains($message, 'vat') || str_contains($message, 'withholding')) {
            return ['type' => 'tax', 'required_plan' => 'enterprise'];
        }

        if (str_contains($message, 'expense')) {
            return ['type' => 'expenses', 'required_plan' => 'basic'];
        }

        if (
            str_contains($message, 'invoices due') ||
            str_contains($message, 'invoice due') ||
            str_contains($message, 'due invoice') ||
            str_contains($message, 'ue today')
        ) {
            return ['type' => 'invoices_due', 'required_plan' => 'basic'];
        }

        if (str_contains($message, 'invoice') && (str_contains($message, 'due') || str_contains($message, 'outstanding') || str_contains($message, 'unpaid'))) {
            return ['type' => 'invoices_due', 'required_plan' => 'basic'];
        }

        if (str_contains($message, 'sale')) {
            return ['type' => 'sales', 'required_plan' => 'basic'];
        }

        if (str_contains($message, 'purchase')) {
            return ['type' => 'purchases', 'required_plan' => 'basic'];
        }

        if (str_contains($message, 'customer')) {
            return ['type' => 'customers_count', 'required_plan' => 'basic'];
        }

        if (str_contains($message, 'vendor') || str_contains($message, 'supplier')) {
            return ['type' => 'vendors_count', 'required_plan' => 'basic'];
        }

        if (str_contains($message, 'product') || str_contains($message, 'inventory item')) {
            return ['type' => 'products_count', 'required_plan' => 'basic'];
        }

        if (
            str_contains($message, 'payment') ||
            str_contains($message, 'payments summary') ||
            str_contains($message, 'total paid')
        ) {
            return ['type' => 'payments', 'required_plan' => 'basic'];
        }

        if (
            str_contains($message, 'users online') ||
            str_contains($message, 'user online') ||
            str_contains($message, 'online users') ||
            str_contains($message, 'online now') ||
            str_contains($message, 'who is online') ||
            str_contains($message, 'active users')
        ) {
            return ['type' => 'online_users', 'required_plan' => 'basic'];
        }

        return null;
    }

    private function intentFromType(string $type): ?array
    {
        return match (strtolower(trim($type))) {
            'sales' => ['type' => 'sales', 'required_plan' => 'basic'],
            'purchases' => ['type' => 'purchases', 'required_plan' => 'basic'],
            'profit_loss' => ['type' => 'profit_loss', 'required_plan' => 'professional'],
            'tax' => ['type' => 'tax', 'required_plan' => 'enterprise'],
            'expenses' => ['type' => 'expenses', 'required_plan' => 'basic'],
            'invoices_due' => ['type' => 'invoices_due', 'required_plan' => 'basic'],
            'general_ledger' => ['type' => 'general_ledger', 'required_plan' => 'enterprise'],
            'trial_balance' => ['type' => 'trial_balance', 'required_plan' => 'enterprise'],
            'balance_sheet' => ['type' => 'balance_sheet', 'required_plan' => 'enterprise'],
            'assistant_intro' => ['type' => 'assistant_intro', 'required_plan' => 'basic'],
            'assistant_help' => ['type' => 'assistant_help', 'required_plan' => 'basic'],
            'customers_count' => ['type' => 'customers_count', 'required_plan' => 'basic'],
            'vendors_count' => ['type' => 'vendors_count', 'required_plan' => 'basic'],
            'products_count' => ['type' => 'products_count', 'required_plan' => 'basic'],
            'payments' => ['type' => 'payments', 'required_plan' => 'basic'],
            'online_users' => ['type' => 'online_users', 'required_plan' => 'basic'],
            default => null,
        };
    }

    private function runIntent(Request $request, string $intent, array $period): JsonResponse
    {
        $label = $period['label'];
        $start = $period['start'];
        $end = $period['end'];

        if ($intent === 'sales') {
            $data = $this->getSalesStats($request, $start, $end);
            return response()->json([
                'ok' => true,
                'answer' => "Total sales for {$label}: ₦" . number_format($data['amount'], 2) . " from {$data['count']} transaction(s).",
                'meta' => $data,
            ]);
        }

        if ($intent === 'assistant_intro') {
            return response()->json([
                'ok' => true,
                'answer' => "Hi, I'm your AI assistant. Ask direct questions like: total sales today, payments this week, customer count, invoices due today.",
            ]);
        }

        if ($intent === 'assistant_help') {
            return response()->json([
                'ok' => true,
                'answer' => "I can answer: sales, purchases, expenses, invoices due, payments, customer/vendor/product counts, trial balance, ledger, balance sheet, and tax (based on your plan access).",
            ]);
        }

        if ($intent === 'purchases') {
            $data = $this->getPurchaseStats($request, $start, $end);
            return response()->json([
                'ok' => true,
                'answer' => "Total purchases for {$label}: ₦" . number_format($data['amount'], 2) . " from {$data['count']} record(s).",
                'meta' => $data,
            ]);
        }

        if ($intent === 'profit_loss') {
            $data = $this->getProfitLossStats($request, $start, $end);
            $status = $data['profit_or_loss'] >= 0 ? 'profit' : 'loss';
            return response()->json([
                'ok' => true,
                'answer' => "Profit/Loss for {$label}: ₦" . number_format($data['profit_or_loss'], 2) . " ({$status}). Sales: ₦" . number_format($data['sales'], 2) . ", Purchases: ₦" . number_format($data['purchases'], 2) . ".",
                'meta' => $data,
            ]);
        }

        if ($intent === 'tax') {
            $data = $this->getTaxStats($request, $start, $end);
            return response()->json([
                'ok' => true,
                'answer' => "Tax summary for {$label}: Sales Tax ₦" . number_format($data['sales_tax'], 2) . ", Purchase Tax ₦" . number_format($data['purchase_tax'], 2) . ", Total Tax ₦" . number_format($data['total_tax'], 2) . ".",
                'meta' => $data,
            ]);
        }

        if ($intent === 'expenses') {
            $data = $this->getExpenseStats($request, $start, $end);
            return response()->json([
                'ok' => true,
                'answer' => "Total expenses for {$label}: ₦" . number_format($data['amount'], 2) . " from {$data['count']} record(s).",
                'meta' => $data,
            ]);
        }

        if ($intent === 'invoices_due') {
            $data = $this->getInvoicesDueStats($request, $start, $end);
            return response()->json([
                'ok' => true,
                'answer' => "Invoices due for {$label}: ₦" . number_format($data['amount'], 2) . " across {$data['count']} invoice(s).",
                'meta' => $data,
            ]);
        }

        if ($intent === 'customers_count') {
            $data = $this->getCustomersCount($request);
            return response()->json([
                'ok' => true,
                'answer' => "Total customers: {$data['count']}.",
                'meta' => $data,
            ]);
        }

        if ($intent === 'vendors_count') {
            $data = $this->getVendorsCount($request);
            return response()->json([
                'ok' => true,
                'answer' => "Total vendors: {$data['count']}.",
                'meta' => $data,
            ]);
        }

        if ($intent === 'products_count') {
            $data = $this->getProductsCount($request);
            return response()->json([
                'ok' => true,
                'answer' => "Total products: {$data['count']}.",
                'meta' => $data,
            ]);
        }

        if ($intent === 'payments') {
            $data = $this->getPaymentStats($request, $start, $end);
            return response()->json([
                'ok' => true,
                'answer' => "Payments for {$label}: ₦" . number_format($data['amount'], 2) . " across {$data['count']} record(s).",
                'meta' => $data,
            ]);
        }

        if ($intent === 'online_users') {
            $data = $this->getOnlineUsersStats($request);
            return response()->json([
                'ok' => true,
                'answer' => "Users online now: {$data['count']} (active within last {$data['window_minutes']} minutes).",
                'meta' => $data,
            ]);
        }

        if ($intent === 'general_ledger') {
            $data = $this->getGeneralLedgerStats($request, $start, $end);
            return response()->json([
                'ok' => true,
                'answer' => "General Ledger summary for {$label}: Debits ₦" . number_format($data['debit'], 2) . ", Credits ₦" . number_format($data['credit'], 2) . " across {$data['count']} entries.",
                'meta' => $data,
            ]);
        }

        if ($intent === 'trial_balance') {
            $data = $this->getTrialBalanceStats($request, $start, $end);
            return response()->json([
                'ok' => true,
                'answer' => "Trial Balance for {$label}: Debits ₦" . number_format($data['debit'], 2) . ", Credits ₦" . number_format($data['credit'], 2) . ", Difference ₦" . number_format($data['difference'], 2) . ".",
                'meta' => $data,
            ]);
        }

        if ($intent === 'balance_sheet') {
            $data = $this->getBalanceSheetStats($request, $end);
            return response()->json([
                'ok' => true,
                'answer' => "Balance Sheet as of {$end->toDateString()}: Assets ₦" . number_format($data['assets'], 2) . ", Liabilities ₦" . number_format($data['liabilities'], 2) . ", Equity ₦" . number_format($data['equity'], 2) . ".",
                'meta' => $data,
            ]);
        }

        return response()->json([
            'ok' => true,
            'answer' => 'Query understood but no handler available yet.',
        ]);
    }

    private function resolvePeriod(string $message): array
    {
        $message = strtolower(trim($message));

        if (
            str_contains($message, 'all time') ||
            str_contains($message, 'overall') ||
            str_contains($message, 'all sales') ||
            str_contains($message, 'all purchase') ||
            str_contains($message, 'sales report')
        ) {
            return [
                'start' => Carbon::now()->subYears(20)->startOfDay(),
                'end' => Carbon::now()->endOfDay(),
                'label' => 'all time',
            ];
        }

        if (str_contains($message, 'last week')) {
            $start = Carbon::now()->subWeek()->startOfWeek();
            $end = Carbon::now()->subWeek()->endOfWeek();
            return [
                'start' => $start,
                'end' => $end,
                'label' => 'last week',
            ];
        }

        if (str_contains($message, 'this week')) {
            return [
                'start' => Carbon::now()->startOfWeek(),
                'end' => Carbon::now()->endOfWeek(),
                'label' => 'this week',
            ];
        }

        if (str_contains($message, 'yesterday')) {
            $day = Carbon::yesterday();
            return [
                'start' => $day->copy()->startOfDay(),
                'end' => $day->copy()->endOfDay(),
                'label' => 'yesterday',
            ];
        }

        if (str_contains($message, 'today')) {
            return [
                'start' => Carbon::today()->startOfDay(),
                'end' => Carbon::today()->endOfDay(),
                'label' => 'today',
            ];
        }

        if (str_contains($message, 'this month')) {
            return [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
                'label' => 'this month',
            ];
        }

        if (str_contains($message, 'last month')) {
            $start = Carbon::now()->subMonthNoOverflow()->startOfMonth();
            $end = Carbon::now()->subMonthNoOverflow()->endOfMonth();
            return [
                'start' => $start,
                'end' => $end,
                'label' => 'last month',
            ];
        }

        if (str_contains($message, 'this year') || str_contains($message, 'year to date') || str_contains($message, 'ytd')) {
            return [
                'start' => Carbon::now()->startOfYear(),
                'end' => Carbon::now()->endOfDay(),
                'label' => 'this year',
            ];
        }

        return [
            'start' => Carbon::today()->startOfDay(),
            'end' => Carbon::today()->endOfDay(),
            'label' => 'today',
        ];
    }

    private function resolveWithModel(string $message): array
    {
        $apiKey = (string) config('services.openai.api_key');
        if ($apiKey === '') {
            return [];
        }

        $model = (string) config('services.openai.model', 'gpt-5-mini');
        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');

        $instruction = "Return only JSON with keys: intent, period. ".
            "intent must be one of: sales,purchases,profit_loss,tax,expenses,invoices_due,general_ledger,trial_balance,balance_sheet,customers_count,vendors_count,products_count,payments,online_users,assistant_intro,assistant_help,unknown. ".
            "period must be one of: today,yesterday,this week,last week,this month,last month,this year,all time. ".
            "If uncertain, set intent to unknown and period to today.";

        try {
            $response = Http::timeout(8)
                ->withToken($apiKey)
                ->post($baseUrl . '/responses', [
                    'model' => $model,
                    'input' => [
                        ['role' => 'system', 'content' => $instruction],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'max_output_tokens' => 80,
                ]);

            if (!$response->ok()) {
                return [];
            }

            $payload = $response->json();
            $text = (string) data_get($payload, 'output_text', '');

            if ($text === '' && is_array(data_get($payload, 'output'))) {
                foreach ((array) data_get($payload, 'output') as $out) {
                    foreach ((array) data_get($out, 'content', []) as $content) {
                        if (($content['type'] ?? null) === 'output_text') {
                            $text .= (string) ($content['text'] ?? '');
                        }
                    }
                }
            }

            if ($text === '') {
                return [];
            }

            $decoded = json_decode(trim($text), true);
            if (!is_array($decoded)) {
                return [];
            }

            return [
                'intent' => strtolower((string) ($decoded['intent'] ?? '')),
                'period' => strtolower((string) ($decoded['period'] ?? '')),
            ];
        } catch (\Throwable $e) {
            Log::warning('AI intent model fallback to rules', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function answerGeneralQuestion(string $message, Request $request): string
    {
        $apiKey = (string) config('services.openai.api_key');
        if ($apiKey === '') {
            return "I can answer business queries now. For open-domain random questions, set OPENAI_API_KEY to enable full assistant mode.";
        }

        $model = (string) config('services.openai.model', 'gpt-5-mini');
        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $history = $this->conversationHistory($request);
        $plan = $this->getCurrentPlan($request);

        $input = [
            [
                'role' => 'system',
                'content' => "You are a smart SaaS assistant inside an accounting app. ".
                    "Be concise and practical. Current user plan: {$plan}. ".
                    "If the user asks for privileged accounting reports beyond plan, tell them to upgrade. ".
                    "For normal random questions, answer directly and clearly.",
            ],
        ];

        foreach ($history as $item) {
            $input[] = ['role' => 'user', 'content' => (string) ($item['q'] ?? '')];
            $input[] = ['role' => 'assistant', 'content' => (string) ($item['a'] ?? '')];
        }
        $input[] = ['role' => 'user', 'content' => $message];

        try {
            $response = Http::timeout(12)
                ->withToken($apiKey)
                ->post($baseUrl . '/responses', [
                    'model' => $model,
                    'input' => $input,
                    'max_output_tokens' => 220,
                ]);

            if (!$response->ok()) {
                return "I couldn't process that right now. Try again in a moment.";
            }

            $payload = $response->json();
            $text = (string) data_get($payload, 'output_text', '');

            if ($text === '' && is_array(data_get($payload, 'output'))) {
                foreach ((array) data_get($payload, 'output') as $out) {
                    foreach ((array) data_get($out, 'content', []) as $content) {
                        if (($content['type'] ?? null) === 'output_text') {
                            $text .= (string) ($content['text'] ?? '');
                        }
                    }
                }
            }

            $text = trim($text);
            if ($text === '') {
                return "I don't have a confident answer yet. Ask me again with a bit more detail.";
            }

            return $text;
        } catch (\Throwable $e) {
            Log::warning('AI general answer fallback', ['error' => $e->getMessage()]);
            return "I couldn't process that right now. Try again in a moment.";
        }
    }

    private function conversationHistory(Request $request): array
    {
        $key = 'ai_chat_history_' . (optional($request->user())->id ?? 'guest');
        $history = $request->session()->get($key, []);
        return array_slice(is_array($history) ? $history : [], -6);
    }

    private function appendConversation(Request $request, string $question, string $answer): void
    {
        $key = 'ai_chat_history_' . (optional($request->user())->id ?? 'guest');
        $history = $request->session()->get($key, []);
        if (!is_array($history)) {
            $history = [];
        }

        $history[] = [
            'q' => trim($question),
            'a' => trim($answer),
            'at' => now()->toDateTimeString(),
        ];

        $request->session()->put($key, array_slice($history, -12));
    }

    private function getSalesStats(Request $request, Carbon $start, Carbon $end): array
    {
        $query = Sale::query();
        $this->applyTenantScope($query, 'sales', $request);

        $dateColumn = Schema::hasColumn('sales', 'order_date') ? 'order_date' : 'created_at';
        $amountColumn = Schema::hasColumn('sales', 'total')
            ? 'total'
            : (Schema::hasColumn('sales', 'total_amount') ? 'total_amount' : 'amount_paid');

        $query->whereBetween($dateColumn, [$start, $end]);

        return [
            'count' => (int) $query->count(),
            'amount' => (float) $query->sum($amountColumn),
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'metric' => 'sales',
        ];
    }

    private function getPurchaseStats(Request $request, Carbon $start, Carbon $end): array
    {
        $query = Purchase::query();
        $this->applyTenantScope($query, 'purchases', $request);

        $dateColumn = Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at';
        $amountColumn = Schema::hasColumn('purchases', 'total_amount')
            ? 'total_amount'
            : (Schema::hasColumn('purchases', 'grand_total') ? 'grand_total' : 'tax_amount');

        $query->whereBetween($dateColumn, [$start, $end]);

        return [
            'count' => (int) $query->count(),
            'amount' => (float) $query->sum($amountColumn),
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'metric' => 'purchases',
        ];
    }

    private function getProfitLossStats(Request $request, Carbon $start, Carbon $end): array
    {
        $sales = $this->getSalesStats($request, $start, $end);
        $purchases = $this->getPurchaseStats($request, $start, $end);

        return [
            'sales' => (float) $sales['amount'],
            'purchases' => (float) $purchases['amount'],
            'profit_or_loss' => (float) $sales['amount'] - (float) $purchases['amount'],
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'metric' => 'profit_loss',
        ];
    }

    private function getTaxStats(Request $request, Carbon $start, Carbon $end): array
    {
        $salesQuery = Sale::query();
        $this->applyTenantScope($salesQuery, 'sales', $request);
        $salesDateColumn = Schema::hasColumn('sales', 'order_date') ? 'order_date' : 'created_at';
        $salesTaxColumn = Schema::hasColumn('sales', 'tax') ? 'tax' : (Schema::hasColumn('sales', 'tax_amount') ? 'tax_amount' : null);
        $salesQuery->whereBetween($salesDateColumn, [$start, $end]);

        $purchaseQuery = Purchase::query();
        $this->applyTenantScope($purchaseQuery, 'purchases', $request);
        $purchaseDateColumn = Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at';
        $purchaseTaxColumn = Schema::hasColumn('purchases', 'tax_amount') ? 'tax_amount' : null;
        $purchaseQuery->whereBetween($purchaseDateColumn, [$start, $end]);

        $salesTax = $salesTaxColumn ? (float) $salesQuery->sum($salesTaxColumn) : 0.0;
        $purchaseTax = $purchaseTaxColumn ? (float) $purchaseQuery->sum($purchaseTaxColumn) : 0.0;

        return [
            'sales_tax' => $salesTax,
            'purchase_tax' => $purchaseTax,
            'total_tax' => $salesTax + $purchaseTax,
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'metric' => 'tax',
        ];
    }

    private function getGeneralLedgerStats(Request $request, Carbon $start, Carbon $end): array
    {
        if (!Schema::hasTable('transactions')) {
            return [
                'count' => 0,
                'debit' => 0.0,
                'credit' => 0.0,
                'from' => $start->toDateTimeString(),
                'to' => $end->toDateTimeString(),
                'metric' => 'general_ledger',
            ];
        }

        $query = Transaction::query()->whereBetween('transaction_date', [$start, $end]);
        $this->applyTenantScope($query, 'transactions', $request);

        return [
            'count' => (int) $query->count(),
            'debit' => (float) $query->sum('debit'),
            'credit' => (float) $query->sum('credit'),
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'metric' => 'general_ledger',
        ];
    }

    private function getTrialBalanceStats(Request $request, Carbon $start, Carbon $end): array
    {
        if (!Schema::hasTable('transactions')) {
            return [
                'debit' => 0.0,
                'credit' => 0.0,
                'difference' => 0.0,
                'count' => 0,
                'from' => $start->toDateTimeString(),
                'to' => $end->toDateTimeString(),
                'metric' => 'trial_balance',
            ];
        }

        $query = Transaction::query()->whereBetween('transaction_date', [$start, $end]);
        $this->applyTenantScope($query, 'transactions', $request);

        $debit = (float) $query->sum('debit');
        $credit = (float) $query->sum('credit');

        return [
            'debit' => $debit,
            'credit' => $credit,
            'difference' => $debit - $credit,
            'count' => (int) $query->count(),
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'metric' => 'trial_balance',
        ];
    }

    private function getBalanceSheetStats(Request $request, Carbon $reportDate): array
    {
        if (!Schema::hasTable('transactions') || !Schema::hasTable('accounts')) {
            return [
                'assets' => 0.0,
                'liabilities' => 0.0,
                'equity' => 0.0,
                'report_date' => $reportDate->toDateString(),
                'metric' => 'balance_sheet',
            ];
        }

        $rows = Transaction::query()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('transaction_date', '<=', $reportDate)
            ->when(!$this->isSuperAdmin($request), function ($q) use ($request) {
                if (Schema::hasColumn('transactions', 'user_id')) {
                    $q->where('transactions.user_id', $request->user()->id);
                }
            })
            ->select('accounts.type')
            ->selectRaw('SUM(transactions.debit) as debit_total')
            ->selectRaw('SUM(transactions.credit) as credit_total')
            ->groupBy('accounts.type')
            ->get();

        $assets = 0.0;
        $liabilities = 0.0;
        $equity = 0.0;
        $revenue = 0.0;
        $expense = 0.0;

        foreach ($rows as $row) {
            $type = strtolower((string) $row->type);
            $dr = (float) $row->debit_total;
            $cr = (float) $row->credit_total;

            if ($type === 'asset') {
                $assets += ($dr - $cr);
            } elseif ($type === 'liability') {
                $liabilities += ($cr - $dr);
            } elseif ($type === 'equity') {
                $equity += ($cr - $dr);
            } elseif ($type === 'revenue') {
                $revenue += ($cr - $dr);
            } elseif ($type === 'expense') {
                $expense += ($dr - $cr);
            }
        }

        $equity += ($revenue - $expense); // retained earnings approximation

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'report_date' => $reportDate->toDateString(),
            'metric' => 'balance_sheet',
        ];
    }

    private function getExpenseStats(Request $request, Carbon $start, Carbon $end): array
    {
        $query = Expense::query()->whereBetween('created_at', [$start, $end]);
        $this->applyTenantScope($query, 'expenses', $request);

        return [
            'count' => (int) $query->count(),
            'amount' => (float) $query->sum('amount'),
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'metric' => 'expenses',
        ];
    }

    private function getInvoicesDueStats(Request $request, Carbon $start, Carbon $end): array
    {
        $query = Invoice::query()
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                if (Schema::hasColumn('invoices', 'status')) {
                    $q->whereIn('status', ['pending', 'unpaid', 'overdue', 'Pending', 'Unpaid', 'Overdue']);
                }
            });

        $this->applyTenantScope($query, 'invoices', $request);

        $amountColumn = Schema::hasColumn('invoices', 'total_amount')
            ? 'total_amount'
            : (Schema::hasColumn('invoices', 'total') ? 'total' : 'amount');

        return [
            'count' => (int) $query->count(),
            'amount' => (float) $query->sum($amountColumn),
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'metric' => 'invoices_due',
        ];
    }

    private function getCustomersCount(Request $request): array
    {
        $query = Customer::query();
        $this->applyTenantScope($query, 'customers', $request);

        return [
            'count' => (int) $query->count(),
            'metric' => 'customers_count',
        ];
    }

    private function getVendorsCount(Request $request): array
    {
        $query = Vendor::query();
        $this->applyTenantScope($query, 'vendors', $request);

        return [
            'count' => (int) $query->count(),
            'metric' => 'vendors_count',
        ];
    }

    private function getProductsCount(Request $request): array
    {
        $query = Product::query();
        $this->applyTenantScope($query, 'products', $request);

        return [
            'count' => (int) $query->count(),
            'metric' => 'products_count',
        ];
    }

    private function getPaymentStats(Request $request, Carbon $start, Carbon $end): array
    {
        $query = Payment::query()->whereBetween('created_at', [$start, $end]);
        $this->applyTenantScope($query, 'payments', $request);

        return [
            'count' => (int) $query->count(),
            'amount' => (float) $query->sum('amount'),
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'metric' => 'payments',
        ];
    }

    private function getOnlineUsersStats(Request $request): array
    {
        $windowMinutes = 5;
        $query = User::query();

        if (!$this->isSuperAdmin($request) && Schema::hasColumn('users', 'company_id') && optional($request->user())->company_id) {
            $query->where('company_id', $request->user()->company_id);
        }

        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (Schema::hasColumn('users', 'last_seen')) {
            $query->where('last_seen', '>=', now()->subMinutes($windowMinutes));
        } elseif (Schema::hasColumn('users', 'status')) {
            $query->whereRaw("LOWER(status) = 'online'");
        } else {
            $query->whereKey(optional($request->user())->id);
        }

        return [
            'count' => (int) $query->count(),
            'window_minutes' => $windowMinutes,
            'metric' => 'online_users',
        ];
    }

    private function applyTenantScope(Builder $query, string $table, Request $request): void
    {
        if ($this->isSuperAdmin($request)) {
            return;
        }

        if (Schema::hasColumn($table, 'company_id') && optional($request->user())->company_id) {
            $query->where('company_id', $request->user()->company_id);
            return;
        }

        if (Schema::hasColumn($table, 'user_id')) {
            $query->where('user_id', $request->user()->id);
            return;
        }

        if (Schema::hasColumn($table, 'created_by')) {
            $query->where('created_by', $request->user()->id);
        }
    }

    private function hasPlanAccess(string $currentPlan, string $requiredPlan, Request $request): bool
    {
        if ($this->isSuperAdmin($request)) {
            return true;
        }

        $rank = ['basic' => 1, 'professional' => 2, 'enterprise' => 3];
        $currentRank = $rank[$currentPlan] ?? 1;
        $requiredRank = $rank[$requiredPlan] ?? 1;

        return $currentRank >= $requiredRank;
    }

    private function getCurrentPlan(Request $request): string
    {
        $user = $request->user();
        $companyPlan = (string) ($user?->company?->plan ?? '');
        $subscriptionPlan = (string) ($user?->subscription?->plan_name ?? $user?->subscription?->plan ?? '');

        $plan = $companyPlan !== '' ? $companyPlan : ($subscriptionPlan !== '' ? $subscriptionPlan : 'basic');
        return $this->normalizePlan($plan);
    }

    private function normalizePlan(string $plan): string
    {
        return match (strtolower(trim($plan))) {
            'pro', 'professional' => 'professional',
            'enterprise' => 'enterprise',
            'basic' => 'basic',
            default => 'basic',
        };
    }

    private function isSuperAdmin(Request $request): bool
    {
        $role = strtolower((string) optional($request->user())->role);
        $email = strtolower((string) optional($request->user())->email);
        return in_array($role, ['super_admin', 'superadmin', 'administrator'], true)
            || $email === 'donvictorlive@gmail.com';
    }
}
