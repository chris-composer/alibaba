<?php

namespace ChrisComposer\Alibaba;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AlibabaSmsServiceProvider extends ServiceProvider
{
    protected $file_name = 'create_table_log_sms.php';

    public function boot(Filesystem $filesystem)
    {
        # 发布配置
        $this->publishes([
            __DIR__ . '/../config/alibaba_sms.php' => config_path('alibaba_sms.php'),
        ], 'config');

        # 发布数据库迁移
        $this->publishes([
            __DIR__ . '/../database/migrations/' . $this->file_name => $this->getMigrationFileName($filesystem)
        ], 'migrations');

        # 发布短信登录 demo
        $this->publishes([
            __DIR__ . '/Sms/Controllers/LoginSmsController.php' => app_path('Http/Controllers/LoginSmsController.php'),
            __DIR__ . '/Sms/Controllers/ComLoginSmsController.php' => app_path('Http/Controllers/ComLoginSmsController.php'),
        ], 'controller');

        # 发布 model demo
        $this->publishes([
            __DIR__ . '/Sms/Models/LogSms.php' => app_path('Models/LogSms.php')
        ], 'models');
    }

    public function register()
    {

    }

    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path . '*_' . $this->file_name);
            })->push($this->app->databasePath() . "/migrations/{$timestamp}_" . $this->file_name)
            ->first();
    }
}
