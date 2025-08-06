<?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           Schema::table('candidates', function (Blueprint $table) {
               // Ubah tipe kolom department_id menjadi unsignedBigInteger
               if (Schema::hasColumn('candidates', 'department_id')) {
                   $table->unsignedBigInteger('department_id')->nullable()->change();
                   // Tambahkan foreign key
                   $table->foreign('department_id')
                         ->references('id')
                         ->on('departments')
                         ->onDelete('set null');
               }
           });
       }

       public function down(): void
       {
           Schema::table('candidates', function (Blueprint $table) {
               if (Schema::hasColumn('candidates', 'department_id')) {
                   $table->dropForeign(['department_id']);
                   // Kembalikan ke tipe sebelumnya jika perlu (opsional)
                   $table->bigInteger('department_id')->nullable()->change();
               }
           });
       }
   };