<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNextStageDateToApplicationStagesTable extends Migration
{
    public function up()
    {
        Schema::table('application_stages', function (Blueprint $table) {
            $table->date('next_stage_date')->nullable();
        });
    }

    public function down()
    {
        Schema::table('application_stages', function (Blueprint $table) {
            $table->dropColumn('next_stage_date');
        });
    }
}