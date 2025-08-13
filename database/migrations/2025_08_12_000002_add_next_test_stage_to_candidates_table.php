<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNextTestStageToCandidatesTable extends Migration
{
    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('next_test_stage')->nullable()->after('next_test_date');
        });
    }

    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('next_test_stage');
        });
    }
}
