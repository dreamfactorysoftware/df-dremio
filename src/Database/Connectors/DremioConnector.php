<?php

namespace DreamFactory\Core\Dremio\Database\Connectors;

use DreamFactory\Core\Dremio\Database\Schema\DremioSchema;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use PDO;

class DremioConnector extends Connector implements ConnectorInterface
{
    public function connect(array $config)
    {
//        $dsn = $this->getDsn($config);
//        return $this->createODBCConnection($dsn);
    }

    public function createODBCConnection($dsn)
    {
//        return odbc_connect($dsn,'', '');
    }

    protected function getDsn(array $config)
    {
//        extract($config, EXTR_SKIP);
//
//        $dsn  = "Driver=/opt/simba/spark/lib/64/libsparkodbc_sb64.so;";
//        $dsn .= "Host={$host};";
//        $dsn .= "Port=443;";
//        $dsn .= "HTTPPath={$http_path};";
//        $dsn .= "AuthMech=3;";
//        $dsn .= "ThriftTransport=2;";
//        $dsn .= "UID=token;";
//        $dsn .= "PWD={$token};";
//        $dsn .= "SSL=1;";
//
//        return $dsn;
    }
}
