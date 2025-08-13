<?php

namespace App\Foundation\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $modulePath;

    public function boot(): void
    {
        $this->loadModuleConfig();
        $this->loadModuleRoutes();
        $this->loadModuleMigrations();
        $this->bootModuleServices();
    }

    public function register(): void
    {
        $this->registerModuleServices();
    }

    protected function bootModuleServices(): void {}
    protected function registerModuleServices(): void {}

    protected function loadModuleConfig(): void
    {
        $configPath = $this->modulePath . '/config';
        if (File::exists($configPath)) {
            foreach (File::files($configPath) as $file) {
                $this->mergeConfigFrom(
                    $file->getPathname(),
                    pathinfo($file, PATHINFO_FILENAME)
                );
            }
        }
    }

    protected function loadModuleRoutes(): void
    {
        $webRoutes = $this->modulePath . '/routes/web.php';
        $apiRoutes = $this->modulePath . '/routes/api.php';
        if (File::exists($webRoutes)) $this->loadRoutesFrom($webRoutes);
        if (File::exists($apiRoutes)) $this->loadRoutesFrom($apiRoutes);
    }

    protected function loadModuleMigrations(): void
    {
        $migrationPath = $this->modulePath . '/database/migrations';
        if (File::exists($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }
}
