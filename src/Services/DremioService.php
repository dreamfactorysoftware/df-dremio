<?php namespace DreamFactory\Core\Dremio\Services;

use DreamFactory\Core\Dremio\Components\DremioComponent;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\RestException;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Dremio\Models\DremioConfig;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Enums\Verbs;
use DreamFactory\Core\SqlDb\Services\SqlDb;
use DreamFactory\Core\SqlDb\Resources\StoredFunction;
use DreamFactory\Core\SqlDb\Resources\StoredProcedure;
use Illuminate\Support\Arr;
use Log;
use DreamFactory\Core\Dremio\Database\Connectors\DremioConnector;
use DreamFactory\Core\Dremio\Database\Schema\DremioSchema;
use DreamFactory\Core\Dremio\Database\Query\DremioQueryBuilder;

/**
 * Class DremioService
 *
 * @package DreamFactory\Core\Dremio\Services
 */
class DremioService extends SqlDb
{
    protected $driverName = 'dremio';

    public function __construct($settings = [])
    {
        parent::__construct($settings);

        $prefix = parent::getConfigBasedCachePrefix();
        $this->setConfigBasedCachePrefix($prefix);
    }

    public static function adaptConfig(array &$config)
    {
        \Log::debug('Adapting Dremio config', ['config' => $config]);
        
        // Set default driver path if not provided
        if (!isset($config['options']['driver_path'])) {
            $config['options']['driver_path'] = env('DREMIO_ODBC_DRIVER_PATH', '/opt/simba/dremio/lib/64/libdremio_odbc_sb64.so');
        }
        
        // Set the driver name
        $config['driver'] = 'dremio';
        
        // Move token to options if it's at the root level
        if (!empty($config['token']) && empty($config['options']['token'])) {
            $config['options']['token'] = $config['token'];
            unset($config['token']);
        }
        
        // Move http_path to options if it's at the root level
        if (!empty($config['http_path']) && empty($config['options']['http_path'])) {
            $config['options']['http_path'] = $config['http_path'];
            unset($config['http_path']);
        }
        
        // Validate required fields
        if (empty($config['options']['token'])) {
            \Log::error('=== DREMIO CONFIG ERROR ===', [
                'error' => 'Token is missing in configuration',
                'config' => array_merge($config, ['options' => ['token' => '***']])
            ]);
            throw new \Exception('Dremio authentication token is required');
        }
        
        if (empty($config['host'])) {
            \Log::error('=== DREMIO CONFIG ERROR ===', [
                'error' => 'Host is missing in configuration',
                'config' => $config
            ]);
            throw new \Exception('Dremio host is required');
        }
        
        if (empty($config['options']['http_path'])) {
            \Log::error('=== DREMIO CONFIG ERROR ===', [
                'error' => 'HTTP path is missing in configuration',
                'config' => $config
            ]);
            throw new \Exception('Dremio HTTP path is required');
        }
        
        \Log::debug('Adapted Dremio config', [
            'host' => $config['host'],
            'http_path' => $config['options']['http_path'],
            'token' => isset($config['options']['token']) ? 'set' : 'not set'
        ]);
    }

    public function getApiDocInfo()
    {
        $base = parent::getApiDocInfo();
        
        // Only keep the _table endpoint with GET method
        $paths = [];
        foreach ($base['paths'] as $path => $methods) {
            if (str_contains($path, '_table')) {
                $paths[$path] = [
                    'get' => $methods['get'] ?? null
                ];
            }
        }
        $base['paths'] = $paths;
        
        $base['description'] = 'Dremio service for connecting to Dremio SQL endpoints.';
        return $base;
    }

    public function getResourceHandlers()
    {
        $handlers = parent::getResourceHandlers();
        
        // Add stored procedure handler
        $handlers['_proc'] = [
            'name'       => 'Stored Procedure',
            'class_name' => StoredProcedure::class,
            'label'      => 'Stored Procedure',
        ];
        
        // Add function handler
        $handlers['_func'] = [
            'name'       => 'Function',
            'class_name' => StoredFunction::class,
            'label'      => 'Function',
        ];
        
        return $handlers;
    }

    public function handleServiceModified($service)
    {
        if (empty($service->name)) {
            $service->name = 'dremio_' . $service->id;
        }
        return parent::handleServiceModified($service);
    }

    public function getConnection()
    {
        $connection = parent::getConnection();
        
        // Log the DSN string
        $config = $this->getConfig();
        $dsn = $this->getDsnString($config);
        
        \Log::debug('=== DREMIO REQUEST DSN ===', [
            'dsn' => $dsn,
            'host' => $config['host'] ?? 'not set',
            'http_path' => $config['options']['http_path'] ?? 'not set',
            'token' => isset($config['options']['token']) ? 'set' : 'not set'
        ]);
        
        return $connection;
    }

    protected function getDsnString($config)
    {
        $useOdbc = $config['use_odbc'] ?? true;
        
        if ($useOdbc) {
            // Validate required fields
            if (empty($config['host'])) {
                throw new \Exception('Dremio host is required');
            }
            
            if (empty($config['options']['http_path'])) {
                throw new \Exception('Dremio HTTP path is required');
            }
            
            if (empty($config['options']['token'])) {
                throw new \Exception('Dremio authentication token is required');
            }
            
            $dsn = "odbc:";
            $dsn .= "Driver=/opt/simba/dremio/lib/64/libdremio_odbc_sb64.so;";
            $dsn .= "Host={$config['host']};";
            $dsn .= "HTTPPath={$config['options']['http_path']};";
            $dsn .= "UID=token;";
            $dsn .= "PWD={$config['options']['token']};";
            $dsn .= "Port=443;";
            $dsn .= "SSL=1;";
            $dsn .= "ThriftTransport=2;";
            $dsn .= "AuthMech=3";
            
            \Log::debug('=== DREMIO DSN CONFIGURED ===', [
                'dsn' => $dsn,
                'host' => $config['host'],
                'http_path' => $config['options']['http_path'],
                'driver_path' => '/opt/simba/dremio/lib/64/libdremio_odbc_sb64.so'
            ]);
        } else {
            $dsn = "dremio:host={$config['host']}";
            $dsn .= !empty($config['port']) ? ":{$config['port']};" : ';';
            $dsn .= "http_path={$config['options']['http_path']};";
            $dsn .= "token={$config['options']['token']};";
            $dsn .= "thrift_transport=2;ssl=1;auth_mech=3";
        }
        
        return $dsn;
    }

    public static function getDriverName()
    {
        return 'dremio';
    }

    private function getHeaderPram($name): array
    {
        return [
            "name" => $name,
            "description" => ucfirst($name) . " for database connection.",
            "schema" => [
                "type" => "string"
            ],
            "in" => "header",
            "required" => false
        ];
    }
}
