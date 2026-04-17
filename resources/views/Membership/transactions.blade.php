<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Configure Tailwind to use Inter font and define custom colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#4f46e5',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">

    <!-- Page Wrapper -->
    <div class="min-h-screen pt-4 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header (Simulated Component) -->
            <div class="pb-6 border-b border-gray-200 mb-6">
                <h1 class="text-3xl font-bold leading-tight text-gray-900">Transactions</h1>
            </div>
            <!-- /Page Header -->

            <!-- Search Filter (Simulated Component) -->
            <div class="mb-6 p-4 bg-white shadow-md rounded-lg flex flex-col md:flex-row items-center space-y-3 md:space-y-0 md:space-x-4">
                <input type="text" placeholder="Search by ID or Type..." class="w-full md:w-1/3 p-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                <select class="w-full md:w-1/6 p-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    <option>All Types</option>
                    <option>Subscription Renewal</option>
                    <option>Initial Payment</option>
                    <option>Refund</option>
                </select>
                <select class="w-full md:w-1/6 p-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    <option>All Statuses</option>
                    <option>Completed</option>
                    <option>Pending</option>
                    <option>Reversed</option>
                </select>
                <button class="w-full md:w-auto p-2 bg-primary text-white font-semibold rounded-md hover:bg-indigo-600 transition duration-150">
                    Filter
                </button>
            </div>
            <!-- /Search Filter -->

            <!-- Table Card -->
            <div class="shadow-lg rounded-xl bg-white overflow-hidden">
                <div class="p-4 sm:p-6 lg:p-8">
                    <div class="overflow-x-auto">
                        <table id="transactions-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tl-xl">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tr-xl">Status</th>
                                </tr>
                            </thead>
                            <tbody id="table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Table rows will be inserted here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /Table Card -->

        </div>
    </div>
    <!-- /Page Wrapper -->

    <script>
        // Mock data structure simulating the PHP file_get_contents and json_decode process
        const transactionsData = [
            {
                "Id": 1001,
                "Type": "Subscription Renewal",
                "Amount": "$199.00",
                "Date": "01 Nov 2024",
                "PaymentType": "Credit Card",
                "Status": "Completed",
                "ClassAmount": "text-green-600",
                "Class": "bg-green-100 text-green-800"
            },
            {
                "Id": 1002,
                "Type": "Initial Payment",
                "Amount": "$99.00",
                "Date": "15 Dec 2024",
                "PaymentType": "PayPal",
                "Status": "Pending",
                "ClassAmount": "text-yellow-600",
                "Class": "bg-yellow-100 text-yellow-800"
            },
            {
                "Id": 1003,
                "Type": "Refund",
                "Amount": "($399.00)",
                "Date": "05 Oct 2024",
                "PaymentType": "Bank Transfer",
                "Status": "Reversed",
                "ClassAmount": "text-red-600",
                "Class": "bg-red-100 text-red-800"
            },
            {
                "Id": 1004,
                "Type": "Subscription Renewal",
                "Amount": "$199.00",
                "Date": "10 Sep 2024",
                "PaymentType": "Stripe",
                "Status": "Completed",
                "ClassAmount": "text-green-600",
                "Class": "bg-green-100 text-green-800"
            },
            {
                "Id": 1005,
                "Type": "Initial Payment",
                "Amount": "$299.00",
                "Date": "15 Jul 2024",
                "PaymentType": "Credit Card",
                "Status": "Completed",
                "ClassAmount": "text-green-600",
                "Class": "bg-green-100 text-green-800"
            }
        ];

        /**
         * Renders the mock transaction data into the HTML table.
         */
        function renderTransactions() {
            const tableBody = document.getElementById('table-body');

            // Clear existing rows
            tableBody.innerHTML = ''; 

            transactionsData.forEach(transaction => {
                const row = document.createElement('tr');
                // Apply subtle hover effect
                row.className = 'hover:bg-gray-100 transition duration-150';

                // Helper function to create table data cell
                const createCell = (content, classes = '') => {
                    const cell = document.createElement('td');
                    cell.className = `px-6 py-4 whitespace-nowrap text-sm text-gray-900 ${classes}`;
                    cell.innerHTML = content;
                    return cell;
                };

                // 1. ID
                row.appendChild(createCell(transaction.Id, 'font-medium text-gray-700'));

                // 2. Type
                row.appendChild(createCell(transaction.Type, 'text-gray-700'));

                // 3. Amount
                // Uses the dynamic ClassAmount from mock data for coloring
                row.appendChild(createCell(`<span class="font-semibold ${transaction.ClassAmount}">${transaction.Amount}</span>`, ''));

                // 4. Date
                row.appendChild(createCell(transaction.Date, 'text-gray-500'));

                // 5. Payment Type
                row.appendChild(createCell(transaction.PaymentType, 'text-gray-500'));

                // 6. Status
                // Uses the dynamic Class from mock data for the status badge
                const statusContent = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${transaction.Class}">
                                             ${transaction.Status}
                                         </span>`;
                row.appendChild(createCell(statusContent, ''));

                tableBody.appendChild(row);
            });
        }

        // Call the render function when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', renderTransactions);

    </script>
</body>
</html>