<?php

use App\Enums\ResourceStatusImport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle_id');
            $table->bigInteger('internal_id');
            $table->string('type');
            $table->string('authors');
            $table->string('item_identifier');
            $table->string('issued_date');
            $table->text('description')->nullable();
            $table->string('publisher');
            $table->enum('status_import', ResourceStatusImport::values())->default(ResourceStatusImport::NOT_IMPORTED->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
