<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function metrics()
    {
        // 1. Suma total de ventas (aseguramos que sea numérico)
        $totalSales = (float) Contract::where('payment_status', 'paid')
            ->orWhere('status', 'completed')
            ->sum('price');

        // 2. Servicios activos
        $servicesActive = Service::where('active', true)->count();

        // 3. Usuarios por rol (Convertimos a un formato más fácil para el frontend)
        $usersCount = User::selectRaw("role_id, count(*) as total")
            ->groupBy('role_id')
            ->get();

        // 4. NUEVO: Datos para la Gráfica de Ventas (últimos 7 días)
        $salesHistory = Contract::where('payment_status', 'paid')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(price) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->take(7)
            ->get();

        return response()->json([
            'total_sales' => $totalSales,
            'services_active' => $servicesActive,
            'users_by_role' => $usersCount,
            'sales_history' => $salesHistory, // Esto dará vida a tus gráficas
            'debug_info' => [
                'total_contracts' => Contract::count(),
                'paid_contracts' => Contract::where('payment_status', 'paid')->count()
            ]
        ]);
    }

    // GET /api/admin/users
    public function users(Request $request)
    {
        $q = User::query();
        if ($request->has('q')) {
            $q->where('name', 'like', '%' . $request->q . '%')->orWhere('email', 'like', "%{$request->q}%");
        }
        $users = $q->orderBy('id', 'desc')->paginate(20);
        return response()->json($users);
    }

    // PATCH /api/admin/users/{id}
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->only(['role_id', 'is_suspended']);
        if (isset($data['role_id'])) $user->role_id = (int)$data['role_id'];
        if (isset($data['is_suspended'])) $user->is_suspended = (bool)$data['is_suspended'];
        $user->save();
        return response()->json(['user' => $user]);
    }

    // GET /api/admin/services
    public function services(Request $request)
    {
        $q = Service::with('user');
        if ($request->has('q')) $q->where('title', 'like', '%' . $request->q . '%');
        $list = $q->orderBy('id', 'desc')->paginate(20);
        return response()->json($list);
    }

    // PATCH /api/admin/services/{id} (toggle active)
    public function toggleService(Request $request, $id)
    {
        $s = Service::findOrFail($id);
        $s->active = $request->boolean('active', !$s->active);
        $s->save();
        return response()->json(['service' => $s]);
    }

    // GET /api/admin/transactions
    public function transactions(Request $request)
    {
        $q = Contract::with(['user', 'service'])->where('payment_status', 'paid');
        if ($request->has('q')) $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$request->q}%"));
        $list = $q->orderBy('created_at', 'desc')->paginate(30);
        return response()->json($list);
    }
}
