<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Keep lightweight; configs can be merged by each ModuleServiceProvider.
    }

    public function boot(): void
    {
        $this->loadModuleRoutes();
        $this->loadModuleMigrations();
    }

    protected function eachModule(callable $cb): void
    {
        $modulesBase = app_path('Modules');
        if (!File::exists($modulesBase)) return;

        // Traverse top-level and one level deeper (Core/User, Learning/Course, etc.)
        foreach (File::directories($modulesBase) as $ns) {
            $cb($ns);
            foreach (File::directories($ns) as $module) {
                $cb($module);
            }
        }
    }

    protected function loadModuleRoutes(): void
    {
        $this->eachModule(function (string $module) {
            $web = $module . '/routes/web.php';
            $api = $module . '/routes/api.php';
            if (File::exists($web)) $this->loadRoutesFrom($web);
            if (File::exists($api)) $this->loadRoutesFrom($api);
        });
    }

    protected function loadModuleMigrations(): void
    {
        $this->eachModule(function (string $module) {
            $migrations = $module . '/database/migrations';
            if (File::exists($migrations)) $this->loadMigrationsFrom($migrations);
        });
    }
}
