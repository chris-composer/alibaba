<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableOssUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = config('alibaba_oss.table.connection');
        $tableName = config('alibaba_oss.table.table_name');

        if (empty($tableName)) {
            throw new \Exception('错误：没有表名，无法创建');
        }

        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $connection = config('alibaba_oss.table.connection');
        $tableName = config('alibaba_oss.table.table_name');

        if (empty($tableName)) {
            throw new \Exception('错误：该数据表不存在，无法删除');
        }

        Schema::connection($connection)->drop($tableName);
    }
}
