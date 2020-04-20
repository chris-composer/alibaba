<?php

namespace ChrisComposer\Alibaba;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AlibabaDingServiceProvider extends ServiceProvider
{
//    protected $file_name = 'create_table_oss_url.php';

    public function boot(Filesystem $filesystem)
    {
        # 发布配置
        $this->publishes([
            __DIR__ . '/../config/alibaba_ding.php' => config_path('alibaba_ding.php'),
        ], 'config');

        # 发布控制器 demo
        $this->publishes([
            __DIR__ . '/Ding/Controllers/LoginDingController.php' => app_path('Http/Controllers'),
            __DIR__ . '/Ding/Controllers/ComLoginDingController.php' => app_path('Http/Controllers'),
        ], 'controller');
    }

    public function register()
    {

    }

    /*protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path . '*_' . $this->file_name);
            })->push($this->app->databasePath() . "/migrations/{$timestamp}_" . $this->file_name)
            ->first();
    }*/
}
