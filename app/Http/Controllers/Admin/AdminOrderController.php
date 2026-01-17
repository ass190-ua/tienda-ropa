<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminOrderController extends Controller
{
    // Método auxiliar para no repetir código de filtros
    private function applyFilters($query, Request $request)
    {
        // 1. Búsqueda (Texto)
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filtro de Estado
        if ($status = $request->input('status')) {
            if ($status !== 'null' && $status !== null) { // Evitar nulos texto
                $query->where('status', $status);
            }
        }

        // 3. Filtro de Fecha
        if ($date = $request->input('date')) {
            switch ($date) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
            }
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = Order::with(['user', 'address', 'payments']);

        // Aplicamos los filtros
        $query = $this->applyFilters($query, $request);

        $orders = $query->orderBy('created_at', 'desc')
                        ->paginate(10);

        return response()->json($orders);
    }

    public function export(Request $request)
    {
        // 1. Cargar pedidos con TODAS las relaciones necesarias
        // ¡Importante! Cargar lines.product.images para poder pintar las fotos
        $query = Order::with(['user', 'address', 'lines.product.images']);

        // 2. Aplicar los mismos filtros que en el listado
        $query = $this->applyFilters($query, $request);

        $orders = $query->orderBy('created_at', 'desc')->get();

        // 3. Renderizar la vista HTML que creamos
        $view = view('admin.exports.orders', compact('orders'))->render();

        // 4. Retornar la respuesta con cabeceras de Excel
        // Esto engaña al navegador para que lo descargue como .xls
        return response($view)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="Listado-Pedidos-' . date('Y-m-d') . '.xls"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function show($id)
    {
        $order = Order::with(['user', 'address', 'lines.product.images', 'payments', 'coupon'])
                      ->findOrFail($id);

        return response()->json($order);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate(['status' => 'required']);
        $order->update(['status' => $request->status]);
        return response()->json($order);
    }
}
