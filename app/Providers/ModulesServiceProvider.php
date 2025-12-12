<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class ModulesServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    $modulesPath = app_path('Modules');

    if (File::exists($modulesPath)) {
      $modules = File::directories($modulesPath);

      foreach ($modules as $module) {
        // Load Routes
        if (File::exists($module . '/Routes/web.php')) {
          Route::middleware('web')
            ->group($module . '/Routes/web.php');
        }

        if (File::exists($module . '/Routes/api.php')) {
          Route::prefix('api')
            ->middleware('api')
            ->group($module . '/Routes/api.php');
        }

        if (is_dir($module . '/resources/views')) {
          $this->loadViewsFrom($module . '/resources/views', basename($module));
        }

        // Load Migrations
        // We typically use standard migrations folder, but can load from here too
        // if (File::isDirectory($module . '/Database/Migrations')) {
        //     $this->loadMigrationsFrom($module . '/Database/Migrations');
        // }
      }
    }
  }
}
