<?php namespace DreamFactory\Core\Dremio\Config;

use DreamFactory\Core\Enums\LicenseLevel;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Dremio\Services\DremioService;
use DreamFactory\Core\Dremio\Models\DremioConfig as DremioConfigModel;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class DremioConfig extends BaseServiceConfigModel
{
    /** @var string */
    protected $table = 'dremio_config';

    /** @var array */
    protected $fillable = [
        'service_id',
        'label',
        'description',
        'host',
        'port',
        'http_path',
        'token',
        'use_odbc',
        'driver_path'
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer',
        'port' => 'integer',
        'use_odbc' => 'boolean'
    ];

    /**
     * {@inheritdoc}
     */
    public static function getConfigSchema()
    {
        $schema = [
            [
                'name' => 'host',
                'type' => 'text',
                'label' => 'Host',
                'description' => 'Your Dremio Host URL',
                'required' => true
            ],
            [
                'name' => 'http_path',
                'type' => 'text',
                'label' => 'HTTP Path',
                'description' => 'The HTTP path for your Dremio cluster',
                'required' => true
            ],
            [
                'name' => 'token',
                'type' => 'password',
                'label' => 'Access Token',
                'description' => 'Your Dremio access token',
                'required' => true
            ],
            [
                'name' => 'driver_path',
                'type' => 'text',
                'label' => 'ODBC Driver Path',
                'description' => 'The path to the Dremio ODBC driver',
                'required' => true,
                'default' => '/opt/arrow-flight-sql-odbc-driver/lib64/libarrow-odbc.so.0.9.5.470'
            ]
        ];

        return $schema;
    }

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'label':
                $schema['label'] = 'Simple label';
                $schema['type'] = 'text';
                $schema['description'] = 'This is just a simple label';
                break;

            case 'description':
                $schema['label'] = 'Description';
                $schema['type'] = 'text';
                $schema['description'] = 'This is just a description';
                break;

            case 'host':
                $schema['label'] = 'Dremio Host';
                $schema['type'] = 'text';
                $schema['required'] = true;
                $schema['description'] = 'Your Dremio Host URL';
                break;

            case 'port':
                $schema['label'] = 'Port';
                $schema['type'] = 'integer';
                $schema['default'] = 443;
                $schema['description'] = 'The port number for the Dremio connection';
                break;

            case 'http_path':
                $schema['label'] = 'HTTP Path';
                $schema['type'] = 'text';
                $schema['required'] = true;
                $schema['description'] = 'The HTTP path for your Dremio cluster';
                break;

            case 'token':
                $schema['label'] = 'Access Token';
                $schema['type'] = 'password';
                $schema['required'] = true;
                $schema['description'] = 'Your Dremio access token';
                break;

            case 'use_odbc':
                $schema['label'] = 'Use ODBC';
                $schema['type'] = 'boolean';
                $schema['default'] = true;
                $schema['description'] = 'Whether to use ODBC for the connection';
                break;

            case 'driver_path':
                $schema['label'] = 'ODBC Driver Path';
                $schema['type'] = 'text';
                $schema['required'] = true;
                $schema['description'] = 'The path to the Dremio ODBC driver';
                break;
        }
    }

    public static function register()
    {
        $serviceType = new ServiceType([
            'name'                  => 'dremio',
            'label'                 => 'Dremio',
            'description'           => 'A service for connecting to Dremio SQL endpoints.',
            'group'                 => ServiceTypeGroups::DATABASE,
            'subscription_required' => LicenseLevel::SILVER,
            'config_handler'        => DremioConfigModel::class,
            'factory'               => function ($config) {
                return new DremioService($config);
            },
        ]);

        ServiceManager::registerServiceType($serviceType);
    }
} 