<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Be Urban</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; margin-top: 40px; margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background-color: #111827; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px; font-weight: 700; }
        .content { padding: 40px 30px; text-align: center; }
        .welcome-text { font-size: 18px; color: #374151; margin-bottom: 20px; line-height: 1.6; }
        .coupon-box {
            background-color: #fffbeb;
            border: 2px dashed #d97706;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            position: relative;
        }
        .coupon-label { display: block; text-transform: uppercase; font-size: 12px; color: #92400e; font-weight: bold; letter-spacing: 1px; margin-bottom: 10px; }
        .coupon-code { font-size: 32px; font-weight: 800; color: #d97706; letter-spacing: 2px; display: block; }
        .btn {
            display: inline-block;
            background-color: #111827;
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
            transition: background 0.3s;
        }
        .legal-text { color: #6b7280; font-size: 13px; margin-top: 25px; line-height: 1.5; border-top: 1px solid #e5e7eb; padding-top: 15px; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BE URBAN</h1>
        </div>

        <div class="content">
            <h2>¡Bienvenido/a a la familia!</h2>

            <p class="welcome-text">
                Estamos encantados de tenerte con nosotros. Prepárate para descubrir las últimas tendencias y estilos únicos.
            </p>

            <p>Como lo prometido es deuda, aquí tienes tu regalo de bienvenida:</p>

            <div class="coupon-box">
                <span class="coupon-label">Tu código de descuento</span>
                <span class="coupon-code">{{ $couponCode }}</span>

                <p style="margin: 10px 0 0 0; color: #92400e; font-size: 14px;">
                    {{ $discount }}% de Descuento
                </p>
            </div>

            <a href="{{ env('FRONTEND_URL', 'https://tienda-moda-9nok.onrender.com') }}" class="btn">
                Ir a la Tienda
            </a>

            <div class="legal-text">
                *Condiciones: Este código es válido para un único uso por cliente.
                Requiere una <strong>compra mínima de {{ $minTotal }}€</strong>.
                No acumulable con otras ofertas.
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Be Urban. Todos los derechos reservados.<br>
            Si no solicitaste este correo, puedes ignorarlo.
        </div>
    </div>
</body>
</html>
