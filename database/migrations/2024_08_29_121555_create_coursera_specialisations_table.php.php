<?php

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
        Schema::create('coursera_specialisations', function (Blueprint $table) {
            
            $table->id();
            $table->string('email');
            $table->string('external_id')->nullable();
            $table->string('specialisaton')->nullable();
            $table->string('specialisaton_slug')->nullable();
            $table->string('university')->nullable();
            $table->date('enrollement_time')->nullable();
            $table->date('last_specialisation_activity')->nullable();
            $table->string('completed_courses')->nullable();
            $table->string('courses_in_specialisation')->nullable();
            $table->string('completed')->nullable();
            $table->string('removed_from_program')->nullable();
            $table->string('enrollment_source')->nullable();
            $table->date('specialization_completion_time')->nullable();
            $table->string('specialization_certificate_url')->nullable();
            $table->foreignId('coursera_member_id')->constrained()->onDelete('cascade')->onUpdate('cascade')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coursera_specialisations');
    }
};
