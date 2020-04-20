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
        $connection = config('alibaba_sms.table.log.connection');
        $tableName = config('alibaba_sms.table.log.table_name');

        if (empty($tableName)) {
            throw new \Exception('错误：没有表名，无法创建');
        }

        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 50); // 短信类型
            $table->json('query'); // 请求参数
            $table->json('response'); // 响应参数
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
        $connection = config('alibaba_sms.table.log.connection');
        $tableName = config('alibaba_sms.table.log.table_name');

        if (empty($tableName)) {
            throw new \Exception('错误：该数据表不存在，无法删除');
        }

        Schema::connection($connection)->drop($tableName);
    }
}
