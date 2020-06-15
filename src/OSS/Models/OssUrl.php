<?php

namespace ChrisComposer\Alibaba\OSS\Models;

use Illuminate\Database\Eloquent\Model;

class OssUrl extends Model
{
    public $timestamps = true;
    protected $guarded = [];

    public function __construct()
    {
        parent::__construct();

        $this->setConnection(config('alibaba_oss.table.connection'));
        $this->setTable(config('alibaba_oss.table.table_name'));
    }
}
