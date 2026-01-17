<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* ESTILOS DE DISEÑO (Igual que antes, que te gustaron) */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        table { width: 100%; border-collapse: collapse; background-color: #ffffff; }

        /* Cabecera */
        thead tr { background-color: #1F2937; color: #ffffff; }
        th {
            background-color: #1F2937;
            color: #ffffff;
            font-weight: bold;
            border: 1px solid #000000;
            padding: 12px;
            text-align: center;
            vertical-align: middle;
        }

        /* Celdas */
        td {
            border: 1px solid #d1d5db;
            padding: 10px;
            vertical-align: middle;
            color: #374151;
        }

        /* Cebra */
        tbody tr:nth-child(even) { background-color: #f3f4f6; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-sm { font-size: 11px; color: #6b7280; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th width="12" style="background-color: #1F2937; color: #ffffff;">ID</th>
                <th width="20" style="background-color: #1F2937; color: #ffffff;">Fecha</th>
                <th width="35" style="background-color: #1F2937; color: #ffffff;">Cliente</th>
                <th width="60" style="background-color: #1F2937; color: #ffffff;">Detalle Productos</th>
                <th width="15" style="background-color: #1F2937; color: #ffffff;">Estado</th>
                <th width="40" style="background-color: #1F2937; color: #ffffff;">Dirección de Envío</th>
                <th width="15" style="background-color: #1F2937; color: #ffffff;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td class="text-center font-bold">#{{ $order->id }}</td>

                <td class="text-center">{{ $order->created_at->format('d/m/Y H:i') }}</td>

                <td>
                    <div class="font-bold">{{ $order->user ? $order->user->name : 'Invitado' }}</div>
                    <div class="text-sm">{{ $order->user ? $order->user->email : '' }}</div>
                </td>

                <td>
                    @foreach($order->lines as $line)
                        @php
                            $imgUrl = null;
                            if($line->product) {
                                // 1. Buscamos la ruta de la imagen (array o propiedad directa)
                                $rawPath = null;
                                if($line->product->images->count() > 0) {
                                    $rawPath = $line->product->images->first()->path;
                                } elseif($line->product->image) {
                                    $rawPath = $line->product->image;
                                }

                                // 2. Generamos la URL absoluta (http://...)
                                if($rawPath) {
                                    if(str_starts_with($rawPath, 'http')) {
                                        $imgUrl = $rawPath;
                                    } else {
                                        // Quitamos la barra inicial si la tiene para que asset() no se líe
                                        $clean = ltrim($rawPath, '/');
                                        // asset() genera http://localhost:8000/storage/... o /images/...
                                        $imgUrl = asset($clean);
                                    }
                                }
                            }
                        @endphp

                        <div style="margin-bottom: 8px; border-bottom: 1px dotted #eee; padding-bottom: 5px;">
                            <table>
                                <tr>
                                    @if($imgUrl)
                                        <td style="border:none; width: 55px; padding: 0;">
                                            <img src="{{ $imgUrl }}" width="50" height="50" style="object-fit: cover; border: 1px solid #ccc;">
                                        </td>
                                    @endif
                                    <td style="border:none; vertical-align: middle;">
                                        <strong>{{ $line->product ? $line->product->name : 'Producto Eliminado' }}</strong>
                                        <br>
                                        <span class="text-sm">x{{ $line->quantity }} ud. — {{ number_format($line->unit_price, 2) }}€</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endforeach
                </td>

                <td class="text-center">
                    @php
                        $colors = [
                            'paid' => '#059669',      // Verde
                            'delivered' => '#059669', // Verde
                            'pending' => '#d97706',   // Naranja
                            'cancelled' => '#dc2626', // Rojo
                            'shipped' => '#2563eb',   // Azul
                        ];
                        $color = $colors[$order->status] ?? '#6b7280';

                        $labels = [
                            'paid' => 'PAGADO', 'pending' => 'PENDIENTE',
                            'delivered' => 'ENTREGADO', 'shipped' => 'ENVIADO',
                            'cancelled' => 'CANCELADO'
                        ];
                        $label = $labels[$order->status] ?? strtoupper($order->status);
                    @endphp
                    <span style="color: {{ $color }}; font-weight: bold;">
                        {{ $label }}
                    </span>
                </td>

                <td>
                    @if($order->address)
                        {{ $order->address->address_line_1 }}<br>
                        {{ $order->address->city }}, {{ $order->address->zip_code }}
                    @else
                        <span class="text-sm font-italic">Sin dirección</span>
                    @endif
                </td>

                <td class="text-right" style="font-size: 15px; font-weight: bold;">
                    {{ number_format($order->total, 2) }} €
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
