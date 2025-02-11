<?php
namespace DreamFactory\Core\Dremio;

use DreamFactory\Core\Components\DbSchemaExtensions;
use DreamFactory\Core\Dremio\Database\Connectors\DremioConnector;
use DreamFactory\Core\Dremio\Database\Schema\DremioSchema;
use DreamFactory\Core\Dremio\Models\DremioConfig;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Enums\LicenseLevel;
use DreamFactory\Core\Dremio\Services\DremioService;
use Illuminate\Routing\Router;
use Illuminate\Database\DatabaseManager;

use Route;
use Event;


class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function register()
    {
        $this->app->resolving('df.db.schema', function (DbSchemaExtensions $db){
            $db->extend('dremio', function ($connection){
                return new DremioSchema($connection);
            });
        });

        $this->app->resolving('db', function (DatabaseManager $db){
            $db->extend('dremio', function ($config){
                $connector = new DremioConnector();
                $connection = $connector->connect($config);

                return new DremioConnection($connection, $config['database'], '', $config);
            });
        });

        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'            => 'dremio',
                    'label'           => 'DremioService Service',
                    'description'     => 'Service for DremioService connections.',
                    'group'           => ServiceTypeGroups::DATABASE,
                    'config_handler'  => DremioConfig::class,
                    'factory'         => function ($config) {
                        return new DremioService($config);
                    },
                ])
            );
        });
    }
}
