<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BlogStarterPostsSeeder extends Seeder
{
    public function run(): void
    {
        $createdCovers = [];
        try {
            DB::transaction(function () use (&$createdCovers) {
                foreach ($this->articles() as $article) {
                    $existingPost = BlogPost::where('slug', $article['slug'])->first();
                    if ($existingPost) {
                        $existingPost->update([
                            'status' => 'published',
                            'published_at' => $existingPost->published_at?->isPast()
                                ? $existingPost->published_at
                                : now(),
                        ]);
                        continue;
                    }

                    $source = public_path('assets/blog-covers/' . $article['image']);
                    $image = is_file($source) ? file_get_contents($source) : false;
                    if ($image === false) {
                        throw new RuntimeException("Unable to read blog cover: {$source}");
                    }
                    $path = 'blog/covers/starter-' . Str::uuid() . '.svg';
                    if (!Storage::disk('public')->put($path, $image)) {
                        throw new RuntimeException("Unable to store blog cover: {$path}");
                    }
                    $createdCovers[] = $path;
                    unset($article['image']);
                    BlogPost::create($article + [
                        'cover_path' => $path,
                        'status' => 'published',
                        'published_at' => now(),
                        'content_format' => 'html',
                    ]);
                }
            });
        } catch (Throwable $exception) {
            foreach ($createdCovers as $path) {
                if (!Storage::disk('public')->delete($path)) {
                    Log::warning('Unable to remove a starter blog cover after seeding failed.', ['path' => $path]);
                }
            }
            throw $exception;
        }
    }

    private function articles(): array
    {
        return [
            [
                'slug' => 'practical-stock-control-for-growing-businesses',
                'title' => 'Practical Stock Control for Growing Businesses',
                'category' => 'Inventory',
                'summary' => 'Build a clearer picture of stock with consistent product records, routine counts and movement reviews.',
                'meta_title' => 'Practical Stock Control for Growing Businesses',
                'meta_description' => 'Learn practical stock control habits for product records, stock counts, sales and branch movements.',
                'meta_keywords' => 'stock control, inventory management, stock movements, branch transfers',
                'image' => 'inventory.svg',
                'content' => <<<'HTML'
<p>Good stock control begins with a simple question: do your records match what is actually on the shelf? When purchases, sales, returns and branch transfers are recorded consistently, it becomes easier to investigate differences and plan the next purchase.</p>
<h2>Start with clear product records</h2>
<p>Give each product a recognizable name and check that its unit of measure is understood by the people who buy, receive and sell it. If you purchase in cartons but sell in pieces, agree on the conversion before recording stock. A consistent starting point makes the movement history easier to interpret.</p>
<h2>Record the movement when it happens</h2>
<p>Enter received purchases, sales and transfers as part of the day-to-day workflow instead of waiting until the end of the week. For branch transfers, confirm both the product and quantity before sending, then check the receiving branch. Keep any supporting documents with the transaction so the team can follow up on discrepancies.</p>
<h2>Count, compare and investigate</h2>
<p>Schedule regular physical counts, starting with fast-moving or high-value products. Compare the count with the recorded balance and review recent movements before making an adjustment. A difference may come from a missed receipt, an incorrect quantity, a return or a transfer that has not been completed.</p>
<h2>Make the review a habit</h2>
<p>Use your SmartProbook inventory records and movement reports to guide conversations with your team. Review low stock and unusual changes regularly, and decide who is responsible for corrections. Accurate records do not remove the need for a physical count, but they make that count far more useful.</p>
HTML,
            ],
            [
                'slug' => 'better-daily-sales-habits-with-pos',
                'title' => 'Better Daily Sales Habits With Your POS',
                'category' => 'Sales',
                'summary' => 'Use a consistent checkout and end-of-day routine to keep sales records easier to review.',
                'meta_title' => 'Better Daily Sales Habits With Your POS',
                'meta_description' => 'Practical checkout and daily reconciliation tips for businesses using a point-of-sale system.',
                'meta_keywords' => 'POS sales, checkout, payment methods, sales reporting',
                'image' => 'pos.svg',
                'content' => <<<'HTML'
<p>A point-of-sale system is most useful when the team follows a reliable routine. From selecting the right product to checking the day's payments, a few consistent steps can make sales records easier to understand.</p>
<h2>Before the first sale</h2>
<p>Review the products and prices your team expects to sell, and confirm that staff are using the appropriate branch and account. Check the physical cash float and the payment options available at the counter. Clear responsibilities help prevent uncertainty when a transaction needs to be corrected.</p>
<h2>During checkout</h2>
<p>Check the item and quantity with the customer before completing the sale. Record the payment method that was actually used and provide the receipt where appropriate. If a sale is cancelled or returned, follow the established process instead of creating an unrelated adjustment; this keeps the transaction trail easier to follow.</p>
<h2>Review the day</h2>
<p>At closing, compare the POS sales report with the receipts and payment records available to the business. Review cash, transfers, cards and other methods separately where your setup supports them. Investigate differences promptly and keep a note of any unresolved issue for the next shift.</p>
<h2>Look beyond the total</h2>
<p>Regularly reviewing sales can show which products move quickly and which periods are busiest. Combine that information with your stock records before ordering more products. SmartProbook brings sales and reporting workflows into one workspace, while your team's checks keep the underlying records dependable.</p>
HTML,
            ],
            [
                'slug' => 'organizing-online-orders-and-stock',
                'title' => 'Organizing Online Orders and Stock',
                'category' => 'Online Selling',
                'summary' => 'Prepare your catalogue, monitor incoming orders and keep staff aligned on fulfilment and cancellations.',
                'meta_title' => 'Organizing Online Orders and Stock',
                'meta_description' => 'Prepare product listings and manage online orders with a clear fulfilment and cancellation routine.',
                'meta_keywords' => 'online store, ecommerce orders, order fulfilment, inventory',
                'image' => 'online-store.svg',
                'content' => <<<'HTML'
<p>Online selling adds convenience for customers, but it also introduces another channel for your team to monitor. A clear catalogue and a shared order process help customers understand what they are buying and help staff know what to do next.</p>
<h2>Prepare the catalogue</h2>
<p>Use clear product names, accurate prices and relevant images. Check descriptions, units and availability before sharing your store with customers. Review listings whenever products or prices change so that the online catalogue stays aligned with your business records.</p>
<h2>Give each order a clear next step</h2>
<p>Assign staff to review incoming orders and confirm the customer's details, payment information and delivery or collection arrangements. Keep communication clear if an item is unavailable or if fulfilment will take longer than expected.</p>
<h2>Handle stock and cancellations carefully</h2>
<p>Review the order status and stock movement together when processing an order. If an order is cancelled, check that the corresponding stock is returned through the order workflow rather than adding an extra manual adjustment. For fulfilled orders, verify the recorded sale and keep the supporting payment information for reporting.</p>
<h2>Review both channels</h2>
<p>Online and counter sales are part of the same business story. Review orders and sales reports regularly, and investigate differences between payment records and recorded transactions. SmartProbook's online store and inventory workflows can help your team keep these activities organized.</p>
HTML,
            ],
            [
                'slug' => 'using-business-reports-to-plan-next-steps',
                'title' => 'Using Business Reports to Plan Your Next Steps',
                'category' => 'Business Insights',
                'summary' => 'Turn regular sales, inventory and financial reviews into practical decisions for your business.',
                'meta_title' => 'Using Business Reports to Plan Your Next Steps',
                'meta_description' => 'Use regular sales, inventory and financial reports to spot questions and plan business decisions.',
                'meta_keywords' => 'business reports, sales reports, inventory insights, financial reporting',
                'image' => 'reports.svg',
                'content' => <<<'HTML'
<p>Reports are more useful when they lead to a question or a decision. A short, regular review of sales, stock and financial records can help you understand what changed and where your team should look next.</p>
<h2>Choose a consistent period</h2>
<p>Compare the same kind of period each time, such as one week with the previous week or a month with the previous month. Check the report filters, branch and date range before drawing conclusions. A change in the selected period can otherwise look like a change in performance.</p>
<h2>Connect sales with inventory</h2>
<p>Identify products that sell frequently and compare their activity with stock on hand. Consider lead times and the reliability of suppliers before placing another order. Slow-moving items deserve attention too: check pricing, visibility and whether the quantities purchased still match demand.</p>
<h2>Review the supporting records</h2>
<p>Look at payment methods, expenses and the underlying transactions rather than relying on a single headline total. Follow up on missing information or unusual entries with the people who handled them. Where financial or tax decisions require specialist advice, consult a qualified professional.</p>
<h2>Turn findings into actions</h2>
<p>Write down a few practical next steps: count a product, confirm an outstanding order, review a price or reconcile a payment. Assign an owner and revisit the action at the next review. SmartProbook reports are most valuable when they support a consistent business routine.</p>
HTML,
            ],
            [
                'slug' => 'how-to-track-business-expenses-small-business',
                'title' => 'How to Track Business Expenses: A Practical Small Business Guide',
                'category' => 'Finance',
                'summary' => 'Build a reliable expense-tracking routine with clear categories, supporting records and regular reconciliation.',
                'meta_title' => 'How to Track Business Expenses | SmartProbook',
                'meta_description' => 'Learn how small businesses can record expenses, organize receipts, reconcile transactions and review spending with a simple routine.',
                'meta_keywords' => 'how to track business expenses, small business expense tracking, expense records, business receipts, expense management',
                'image' => 'expense-tracking.svg',
                'content' => <<<'HTML'
<p>Knowing where business money goes starts with complete, consistent records. A practical expense-tracking routine helps you review spending, prepare for conversations with an accountant and spot missing transactions. It does not need to be complicated, but it does need to happen regularly.</p>
<h2>Record each expense while it is fresh</h2>
<p>Enter the date, supplier, amount, payment method and business purpose as soon as possible after a purchase. Add a useful description instead of relying on a vague note such as “supplies.” If a receipt or invoice is available, keep it with the transaction so you can explain the entry later.</p>
<h2>Use consistent expense categories</h2>
<p>Agree on a short list of categories that reflects how your business operates, such as transport, utilities, rent, supplies or payment fees. Apply the same category to similar purchases each time. If you are unsure how an item should be treated for accounting or tax purposes, ask a qualified local professional rather than guessing.</p>
<h2>Keep business and personal spending distinct</h2>
<p>Use designated business accounts and payment methods where possible. If a business expense is paid personally, document the details and follow your accountant’s guidance for recording it. Clear separation makes the records easier to review and reduces uncertainty when reconciling balances.</p>
<h2>Reconcile expenses against actual payments</h2>
<p>At a regular interval, compare recorded expenses with bank, card, cash and mobile-payment records that apply to your business. Check for duplicate entries, missing receipts, incorrect amounts and transactions assigned to the wrong account. Correct mistakes through your normal adjustment process so the history remains understandable.</p>
<h2>Turn the records into a useful review</h2>
<p>Look at spending by category and period, then ask practical questions: has a recurring cost changed, is a supplier charge unexpected, or is an important receipt missing? Assign follow-up actions and review them again. Avoid making a decision from one total without checking the underlying transactions and the period covered.</p>
<h2>A simple weekly expense checklist</h2>
<ul>
<li>Enter new expenses and attach available supporting documents.</li>
<li>Check the payment method, category and business purpose.</li>
<li>Compare recorded transactions with the payment records available.</li>
<li>Follow up on missing information and unusual entries.</li>
</ul>
<p>SmartProbook expense and transaction records can help keep this routine in one workspace. Consistent entry and review by your team are what make those records dependable.</p>
HTML,
            ],
            [
                'slug' => 'small-business-invoice-management-guide',
                'title' => 'Small Business Invoice Management: Create, Send and Track Invoices',
                'category' => 'Invoicing',
                'summary' => 'Use a consistent invoicing process to make payment terms clear, monitor due dates and follow up on unpaid invoices.',
                'meta_title' => 'Small Business Invoice Management Guide | SmartProbook',
                'meta_description' => 'Learn how to create clear invoices, set payment terms, track due dates and follow up while keeping customer and payment records organized.',
                'meta_keywords' => 'small business invoice management, how to create an invoice, invoice tracking, payment terms, unpaid invoices',
                'image' => 'invoice-management.svg',
                'content' => <<<'HTML'
<p>A clear invoicing process helps customers understand what they owe and helps your team know what needs follow-up. Whether you send a few invoices or manage them every day, consistent details and timely status updates make the payment trail easier to review.</p>
<h2>Include the details customers need</h2>
<p>Use a unique invoice number and include the issue date, your business details, the customer details, a description of the goods or services, quantities and agreed prices. Show any applicable charges and the total clearly. Invoice rules differ by location and business type, so confirm required fields with a qualified local adviser.</p>
<h2>Make payment terms easy to find</h2>
<p>State the due date, accepted payment methods and the contact point for questions. Agree on the terms with the customer before work begins where possible. If a deposit, milestone or partial payment applies, spell out the amount and when it is due rather than relying on an informal message.</p>
<h2>Send invoices promptly and keep a record</h2>
<p>Send the invoice to the correct contact after the agreed delivery or milestone. Keep a copy and record when it was sent. Check that the customer can open the document and knows how to ask about a discrepancy. A short, professional message with the invoice number and due date makes later follow-up more straightforward.</p>
<h2>Track status and follow up consistently</h2>
<p>Maintain a simple status for each invoice, such as draft, sent, partially paid, paid or overdue. Review upcoming and overdue due dates on a schedule. Send a courteous reminder with the invoice reference and outstanding amount, and record any agreed new date or payment plan. If a customer disputes an item, investigate the original order or service record before changing the invoice.</p>
<h2>Match payments to the right invoice</h2>
<p>When money arrives, verify the amount and reference against the payment record before marking an invoice paid. Record partial payments accurately and keep the remaining balance visible. At regular intervals, compare invoice statuses with your bank or payment-provider records so that an unpaid invoice is not mistaken for received cash.</p>
<h2>Build a repeatable invoicing routine</h2>
<ul>
<li>Check customer and item details before sending.</li>
<li>Set clear due dates and payment terms.</li>
<li>Review due and overdue invoices at a consistent time.</li>
<li>Update invoice status only after checking the payment record.</li>
</ul>
<p>SmartProbook invoicing and payment records can help your team keep customer balances and follow-ups organized. For tax treatment, statutory invoice requirements or debt-recovery questions, get advice from a qualified professional in your jurisdiction.</p>
HTML,
            ],
        ];
    }
}
