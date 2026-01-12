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
        $query = Order::with(['user', 'address']);

        // Aplicamos los filtros
        $query = $this->applyFilters($query, $request);

        $orders = $query->orderBy('created_at', 'desc')
                        ->paginate(10);

        return response()->json($orders);
    }

    // ¡NUEVO! Función para Exportar a CSV
    public function export(Request $request)
    {
        $query = Order::with(['user', 'address']);

        // Aplicamos los MISMOS filtros que en la lista (para exportar lo que ves)
        $query = $this->applyFilters($query, $request);

        $orders = $query->orderBy('created_at', 'desc')->get();

        $filename = "pedidos-" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');

            // Encabezados del CSV (UTF-8 BOM para que Excel lea bien los acentos)
            fputs($file, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
            fputcsv($file, ['ID', 'Cliente', 'Email', 'Fecha', 'Estado', 'Total', 'Dirección']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->user ? $order->user->name : 'Invitado',
                    $order->user ? $order->user->email : '',
                    $order->created_at->format('d/m/Y H:i'),
                    $order->status,
                    $order->total_amount ?? $order->subtotal, // Ajuste según tu modelo
                    $order->address ? ($order->address->address_line_1 . ' ' . $order->address->city) : 'S/D'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show($id)
    {
        $order = Order::with(['user', 'address', 'lines.product'])->findOrFail($id);
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
