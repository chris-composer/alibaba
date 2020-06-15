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
            $table->string('md5_file', 100)->nullable();
            $table->string('oss_url', 255)->nullable();
            $table->timestampsTz(); // 相当于可空且带时区的 created_at 和 updated_at TIMESTAMP
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
