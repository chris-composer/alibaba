<?php

namespace ChrisComposer\Alibaba\OSS\Models;

use Illuminate\Database\Eloquent\Model;

class OssUrl extends Model
{
    protected $guarded = [];

    public function __construct()
    {
        parent::__construct();

        $this->connection = config('alibaba_oss.table.connection');
        $this->table = config('alibaba_oss.table.table_name');
    }
}
