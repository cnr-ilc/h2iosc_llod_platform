<?php

use App\Enums\GeneralStatus;
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
        Schema::create('resource_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->text('path');
            $table->string('name');
            $table->integer('size');
            $table->string('mime_type');
            $table->text('description')->nullable();
            $table->string('graphdb_uri')->nullable();
            $table->string('graphdb_repository_name')->nullable();
            $table->string('st_project_name')->nullable();
            $table->string('hash_md5');
            //
            $table->json('health_issues')->nullable(); // array of strings
            $table->json('health_warnings')->nullable(); // array of strings
            $table->json('quality_result')->nullable(); // JSON object
            $table->json('quality_warnings')->nullable(); // array of strings
            $table->json('quality_score')->nullable(); // JSON object
            $table->boolean('can_be_opened_in_vocbench')->default(false);
            $table->boolean('is_submittable')->default(true);
            $table->boolean('is_synchronising')->default(false);
            $table->timestamp('validation_timestamp')->nullable();
            $table->timestamp('quality_assessment_timestamp')->nullable();
            $table->enum('validation_status', GeneralStatus::values())->default(GeneralStatus::RUNNING->value);
            $table->enum('quality_assessment_status', GeneralStatus::values())->nullable();
            //
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_files');
    }
};
