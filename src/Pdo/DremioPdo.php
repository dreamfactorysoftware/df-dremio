<?php namespace DreamFactory\Core\Dremio\Pdo;

use DreamFactory\Core\SqlDb\Pdo\Odbc;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Log;

class DremioPdo extends Odbc
{
    public function __construct($dsn, $username = null, $password = null, array $options = [])
    {
        try {
            Log::debug('=== DREMIO PDO CONNECTION ATTEMPT ===', [
                'dsn' => $dsn,
                'username' => $username,
                'options' => $options
            ]);
            
            parent::__construct($dsn, $username, $password, $options);
            
            Log::debug('=== DREMIO PDO CONNECTION SUCCESS ===');
        } catch (\PDOException $e) {
            Log::error('=== DREMIO PDO CONNECTION ERROR ===', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'dsn' => $dsn
            ]);
            
            throw new InternalServerErrorException(
                "Failed to connect to Dremio server: " . $e->getMessage()
            );
        }
    }
} 