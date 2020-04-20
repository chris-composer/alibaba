<?php

namespace ChrisComposer\Alibaba;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AlibabaOssServiceProvider extends ServiceProvider
{
    protected $file_name = 'create_table_oss_url.php';

    public function boot(Filesystem $filesystem)
    {
        $this->publishes([
            __DIR__ . '/../config/alibaba_oss.php' => config_path('alibaba_oss.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' . $this->file_name => $this->getMigrationFileName($filesystem)
        ], 'migrations');
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
