<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        $this->loadModuleConfigs();
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        $this->loadModuleRoutes();
        $this->loadModuleMigrations();
    }

    /**
     * Auto-load config files from each module.
     */
    protected function loadModuleConfigs(): void
    {
        $modulesPath = app_path('Modules');

        if (File::exists($modulesPath)) {
            foreach (File::directories($modulesPath) as $module) {
                $configPath = $module . '/config';
                if (File::exists($configPath)) {
                    foreach (File::files($configPath) as $file) {
                        $this->mergeConfigFrom($file->getPathname(), pathinfo($file, PATHINFO_FILENAME));
                    }
                }
            }
        }
    }

    /**
     * Auto-load routes from each module.
     */
    protected function loadModuleRoutes(): void
    {
        $modulesPath = app_path('Modules');

        if (File::exists($modulesPath)) {
            foreach (File::allFiles($modulesPath) as $file) {
                if ($file->getFilename() === 'web.php') {
                    $this->loadRoutesFrom($file->getPathname());
                }
                if ($file->getFilename() === 'api.php') {
                    $this->loadRoutesFrom($file->getPathname());
                }
            }
        }
    }

    /**
     * Auto-load migrations from each module.
     */
    protected function loadModuleMigrations(): void
    {
        $modulesPath = app_path('Modules');

        if (File::exists($modulesPath)) {
            foreach (File::directories($modulesPath) as $module) {
                $migrationsPath = $module . '/database/migrations';
                if (File::exists($migrationsPath)) {
                    $this->loadMigrationsFrom($migrationsPath);
                }

                // Recursively check submodules (e.g., Learning/Course)
                foreach (File::directories($module) as $subModule) {
                    $subMigrations = $subModule . '/database/migrations';
                    if (File::exists($subMigrations)) {
                        $this->loadMigrationsFrom($subMigrations);
                    }
                }
            }
        }
    }
}
