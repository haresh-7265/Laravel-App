<?php
namespace App\Services\Reports;

use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerReportService
{
    public function generate(): array
    {
        // Only role = 'customer' users (based on your 'role' column)
        $newRegistrations = User::where('role', 'customer')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $totalCustomers = User::where('role', 'customer')->count();

        // Top buyers using 'total' column and relationship
        $topBuyers = Order::select(
            'user_id',
            DB::raw('SUM(total) as total_spent'),
            DB::raw('COUNT(*) as order_count')
        )
            ->where('status', 'delivered')
            ->whereHas('user', fn($q) => $q->where('role', 'customer'))
            ->with('user:id,name,email')           // uses your User relationship
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get()
            ->map(fn($o) => [
                'name' => $o->user->name ?? 'Unknown',
                'email' => $o->user->email ?? 'N/A',
                'total_orders' => $o->order_count,
                'total_spent' => number_format($o->total_spent, 2),
            ])->toArray();

        // Inactive: customers with no orders in last 90 days
        $cutoff = Carbon::now()->subDays(90);

        $inactiveUsers = User::select([
            'users.name',
            'users.email',
            DB::raw('DATE(MAX(orders.created_at)) as last_order_at'),
            DB::raw('DATEDIFF(NOW(), MAX(orders.created_at)) as days_inactive'),
            DB::raw('DATE(users.created_at) as created_at'),
        ])
            ->leftJoin('orders', 'orders.user_id', '=', 'users.id')
            ->where('users.role', 'customer')
            ->where('users.created_at', '<=', $cutoff)
            ->groupBy('users.id', 'users.name', 'users.email', 'users.created_at')
            ->havingRaw('MAX(orders.created_at) <= ? OR MAX(orders.created_at) IS NULL', [$cutoff])
            ->get()
            ->map(fn($user) => [
                'name' => $user->name,
                'email' => $user->email,
                'last_order_at' => $user->last_order_at ?? 'Never',
                'days_inactive' => $user->days_inactive
                    ?? Carbon::parse($user->created_at)->diffInDays(now()),
            ])
            ->toArray();

        return [
            'generated_at' => now()->toDateTimeString(),
            'total_customers' => $totalCustomers,
            'new_registrations' => $newRegistrations,
            'top_buyers' => $topBuyers,
            'inactive_users' => $inactiveUsers,
        ];
    }

    public function summaryRows(array $data): array
    {
        $topBuyer = $data['top_buyers'][0] ?? [];
        return [
            ['Total Customers', $data['total_customers']],
            ['New (Last 30 days)', $data['new_registrations']],
            ['Inactive (90d+)', count($data['inactive_users'] ?? [])],
            ['Top Buyer', $topBuyer['name'] ?? 'N/A'],
            ['Top Buyer Spent', config('admin.currency') . ($topBuyer['total_spent'] ?? '0.00')],
        ];
    }

    public function csvRows(array $data): array
    {
        $rows = [];

        // === HEADER ===
        $rows[] = ['Customer Report'];
        $rows[] = ['Generated At', $data['generated_at']];
        $rows[] = [];

        // === SUMMARY ===
        $rows[] = ['Summary'];
        foreach ($this->summaryRows($data) as $row) {
            $rows[] = $row;
        }
        $rows[] = [];

        // === TOP BUYERS ===
        $rows[] = ['Top Buyers (Last 5 by Spend)'];
        $rows[] = ['Name', 'Email', 'Total Orders', 'Total Spent'];

        if (!empty($data['top_buyers'])) {
            foreach ($data['top_buyers'] as $buyer) {
                $rows[] = [
                    $buyer['name'],
                    $buyer['email'],
                    $buyer['total_orders'],
                    $buyer['total_spent'],
                ];
            }
        } else {
            $rows[] = ['No buyer data available'];
        }

        $rows[] = [];

        // === INACTIVE USERS ===
        $rows[] = ['Inactive Customers (90+ Days)'];
        $rows[] = ['Name', 'Email', 'Last Order Date', 'Days Inactive'];

        if (!empty($data['inactive_users'])) {
            foreach ($data['inactive_users'] as $user) {
                $rows[] = [
                    $user['name'],
                    $user['email'],
                    $user['last_order_at'],
                    $user['days_inactive'],
                ];
            }
        } else {
            $rows[] = ['No inactive customers found'];
        }

        return $rows;
    }
}