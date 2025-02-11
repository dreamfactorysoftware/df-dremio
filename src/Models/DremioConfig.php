<?php

namespace DreamFactory\Core\Dremio\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;
use Illuminate\Support\Arr;

/**
 * Class DremioService
 *
 * @package DreamFactory\Core\DremioService\Models
 */
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
        'token',
        'http_path',
        'driver_path',
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer',
    ];

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
                $schema['description'] =
                    'This is just a description';
                break;
            case 'host':
                $schema['label'] = 'Dremio Host';
                $schema['type'] = 'text';
                $schema['required'] = true;
                $schema['description'] =
                    'Your DremioService Host URL';
                break;
            case 'token':
                $schema['label'] = 'Dremio API Token';
                $schema['type'] = 'text';
                $schema['required'] = true;
                $schema['description'] =
                    'Your Dremio API Token';
                break;
            case 'http_path':
                $schema['label'] = 'Dremio HTTP Path';
                $schema['type'] = 'text';
                $schema['required'] = true;
                $schema['description'] =
                    'Your Dremio HTTP Path';
                break;
            case 'driver_path':
                $schema['label'] = 'Dremio ODBC Driver Path';
                $schema['type'] = 'text';
                $schema['required'] = true;
                $schema['description'] =
                    'Your ODBC Driver Path';
                break;
        }
    }


}
