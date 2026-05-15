<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderAnalytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use PDOException;

class AnalyticsController extends Controller
{
    public function index(): JsonResponse
    {
        DB::beginTransaction();
        try {

            // main DB — via Eloquent (mysql)
            $orders = Order::where('status', 'delivered')
                ->select('id', 'user_id', 'order_number', 'status', 'total', 'discount')
                ->limit(5)
                ->get();

            // analytics DB — via Eloquent model
            $analytics = OrderAnalytics::where('region', 'IN')
                ->limit(5)
                ->get();

            // analytics DB — via raw DB::connection()
            $revenue = DB::connection('analytics')
                ->select('SELECT region, SUM(revenue) as total FROM order_analytics GROUP BY region');

            $orderModelConnection = (new Order)->getConnection()->getName();
            $orderAnalyticsModelConnection = (new OrderAnalytics)->getConnection()->getName();

            throw new PDOException('database conection error');
            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'orders' => $orders,
                    'analytics' => $analytics,
                    'revenue' => $revenue,
                    'order_model_conn' => $orderModelConnection,
                    'order_analytics_model_conn' => $orderAnalyticsModelConnection,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
