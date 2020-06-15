<?php

namespace ChrisComposer\Alibaba\Sms\Models;

use Illuminate\Database\Eloquent\Model;

class LogSms extends Model
{
    public $timestamps = true;
    protected $guarded = [];

    public function __construct()
    {
        parent::__construct();

        $this->setConnection(config('alibaba_sms.table.log.connection'));
        $this->setTable(config('alibaba_sms.table.log.table_name'));
    }
}
