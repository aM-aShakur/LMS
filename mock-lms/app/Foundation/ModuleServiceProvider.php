<?php

namespace App\Foundation;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName; // e.g. 'Core/User'
    protected string $modulePath;

    public function register(): void
    {
        $this->modulePath = app_path('Modules/' . trim($this->moduleName, '/'));
    }

    public function boot(): void
    {
        $this->loadModuleConfig();
        $this->loadModuleRoutes();
        $this->loadModuleMigrations();
        $this->loadModuleViews();
    }

    protected function loadModuleConfig(): void
    {
        $configDir = $this->modulePath . '/config';
        if (!File::exists($configDir)) return;

        foreach (File::files($configDir) as $file) {
            if ($file->getExtension() !== 'php') continue;
            $key = $file->getFilenameWithoutExtension();
            $this->mergeConfigFrom($file->getPathname(), $key);
        }
    }

    protected function loadModuleRoutes(): void
    {
        $web = $this->modulePath . '/routes/web.php';
        $api = $this->modulePath . '/routes/api.php';
        if (File::exists($web)) $this->loadRoutesFrom($web);
        if (File::exists($api)) $this->loadRoutesFrom($api);
    }

    protected function loadModuleMigrations(): void
    {
        $path = $this->modulePath . '/database/migrations';
        if (File::exists($path)) $this->loadMigrationsFrom($path);
    }

    protected function loadModuleViews(): void
    {
        $path = $this->modulePath . '/resources/views';
        if (File::exists($path)) $this->loadViewsFrom($path, str_replace('/', '_', $this->moduleName));
    }
}
