<?php

namespace ChrisComposer\Alibaba\OSS\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ComException extends HttpException
{
    public $msg;
    public $code;
    public $response_code;

    public function __construct($msg, $code, $response_code = 500)
    {
        parent::__construct($code);
        $this->msg = $msg;
        $this->code = $code;
        $this->response_code = $response_code;
    }
}