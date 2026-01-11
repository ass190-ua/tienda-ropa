<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('url')->unique();

            $table->string('description_short');
            $table->text('description_long')->nullable();

            $table->decimal('price', 10, 2);

            // Atributos (1 valor por columna)
            $table->foreignId('color_id')->nullable()->constrained('attribute_values');
            $table->foreignId('size_id')->nullable()->constrained('attribute_values');
            $table->foreignId('category_id')->nullable()->constrained('attribute_values');
            $table->foreignId('type_id')->nullable()->constrained('attribute_values');

            // Descuentos
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->timestamp('discount_start')->nullable();
            $table->timestamp('discount_end')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
