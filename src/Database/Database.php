<?php
class Database
{
    public PDO $pdo;
    public function __construct(private array $config) 
    {

    }

    public function getConnection(): PDO
    {
        $options = [];
        $driver = $this->config['driver'];
        switch ($driver) {
            case 'mysql':
                $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    // PDO::ATTR_EMULATE_PREPARES => false,
                    // PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                    // PDO::MYSQL_ATTR_SSL_CA => '/path/to/cacert.pem', // Для SSL
                ];

                break;
            case 'sqlsrv':
                $dsn = "sqlsrv:Server={$this->config['host']};Database={$this->config['dbname']}";
                break;
            default:
                throw new InvalidArgumentException("Неподдерживаемый драйвер: $driver");
        }
        
        return new PDO($dsn, $this->config['username'], $this->config['password'], $options);
    }

    /**
     * В классе Database
     * @param string $sql
     * @param array $params
     */
    /*public function execute(string $sql, array $params = [])
    {
        $this->pdo = $this->getConnection();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }*/

    /**
     * Или если у вас уже есть метод query, сделайте его универсальным:
     * @param string $sql
     * @param array $params
     */
    public function query(string $sql, array $params = [])
    {
        $this->pdo = $this->getConnection();
        if (empty($params)) {
            return $this->pdo->query($sql);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }
    }



    /*
        public function __construct(private string $host, private string $dataname, private string $username, 
        private string $password, private string $charset = 'utf8mb4') {}

        public function getConnection(): PDO
        {
            //sqlsrv:Server=91.201.55.41;Database=ConstructionAccounting_test
            $dsn = "sqlsrv:Server={$this->host};Database={$this->dataname}";

            //$dsn = "mysql:host={$this->host};dbname={$this->dataname};charset=utf8mb4";

            //$dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server={$this->host};Database={$this->dataname};";

            return new PDO($dsn, $this->username, $this->password);
        }

        public function getConnection(): PDO
        {
            $dsn = "mysql:host={$this->host};dbname={$this->dataname};charset={$this->charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
               // PDO::ATTR_EMULATE_PREPARES => false,
               // PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
               // PDO::MYSQL_ATTR_SSL_CA => '/path/to/cacert.pem', // Для SSL
            ];

            try {
                return new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                throw new PDOException("Connection failed: " . $e->getMessage());
            }
        }*/
}
