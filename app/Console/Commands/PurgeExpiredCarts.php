<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeExpiredCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:purge-expired {--ttl= : TTL en minutos (por defecto CART_TTL_MINUTES o 15)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina cart_items de carritos inactivos (caducados por updated_at) para limpiar reservas antiguas.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ttl = (int) ($this->option('ttl') ?: env('CART_TTL_MINUTES', 15));
        $ttl = max(1, $ttl);

        $cutoff = now()->subMinutes($ttl);

        // 1) Encontrar carritos caducados (por updated_at)
        $expiredCartIds = DB::table('carts')
            ->where('updated_at', '<', $cutoff)
            ->pluck('id');

        if ($expiredCartIds->isEmpty()) {
            $this->info("No hay carritos caducados (TTL={$ttl}m).");
            return 0;
        }

        // 2) Borrar sus líneas (cart_items)
        $deletedItems = DB::table('cart_items')
            ->whereIn('cart_id', $expiredCartIds)
            ->delete();

        // 3) (Opcional) borrar carritos sin items (por limpieza total)
        // Si prefieres NO borrar carts, comenta este bloque.
        $deletedCarts = DB::table('carts')
            ->whereIn('id', $expiredCartIds)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('cart_items')
                    ->whereColumn('cart_items.cart_id', 'carts.id');
            })
            ->delete();

        $this->info("Purgados: cart_items={$deletedItems}, carts={$deletedCarts} (TTL={$ttl}m).");
        return 0;
    }
}
