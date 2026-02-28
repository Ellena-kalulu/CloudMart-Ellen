<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // (Stationery, Dairy)
            $table->string('slug')->unique();       //  (stationery, dairy)
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
