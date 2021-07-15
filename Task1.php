<?php

require_once('config.php');

class DataBase
{
    private ?string $dsn;
    private ?array $options;
    private $host = HOST;
    private $db = DATABASE;
    private $connection;
    private static $database;

    private function __clone() {}
    private function __wakeup() {}

    private function __construct()
    {
        $this->createConnection();
    }

    private function createConnection():void
    {
        $this->dsn = "pgsql:host=$this->host;dbname=$this->db";
        $this->options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false);
        $this->connection = new PDO($this->dsn, USER,PASSWORD, $this->options);
    }

    public static function getInstance():DataBase
    {
        if (null == self::$database) {
            self::$database = new self();
        }
        return self::$database;
    }

    public function getConnection():PDO
    {
        return $this->connection;
    }

    /**
     * @return array
     */
    private function returnData() : array
    {
        $data = self::getInstance()->getConnection()->prepare("SELECT * FROM university ORDER BY id");
        $data ->execute();
        $result = $data->fetchAll();
        return $result;
    }

    /**
     * @param int $start_id
     * @return false|string
     * @throws Exception
     */
    public function buildTree(int $start_id = 1) : string
    {
        if (intval($start_id)!==$start_id) {
            throw new Exception("INCORRECT INPUT: argument 'start_id' must be integer.");
        }

        $data = $this->returnData();
        $data = array_combine(range(1, count($data)), array_values($data));

        foreach ($data as $key => $value) {
            $data[$value['parent_id']]['sub_divisions'][] = &$data[$key];
        }

        return json_encode($data[$start_id], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param int|null $id
     * @return string
     * @throws Exception
     */
    public function getInformation(?int $id) : string
    {
            if (intval($id)!==$id) {
                throw new Exception("INCORRECT INPUT: argument 'id' must be integer.");
            }

            $query = "SELECT * FROM university WHERE id =:id";
            $data = self::getInstance()->getConnection()->prepare($query);
            $data->execute(['id' => $id]);
            $result = $data->fetchAll();

            return json_encode($result, JSON_UNESCAPED_UNICODE);
    }


    /**
     * @param int|null $id
     * @param string|null $name
     * @throws Exception
     */
    public function updateName(?int $id, ?string $name)
    {
        if (intval($id)!==$id) {
            throw new Exception("INCORRECT INPUT: argument 'id' must be integer.");
        }
        if (strpos($name,"--") || strpos($name,",")) {
            throw new Exception("INCORRECT INPUT: forbidden symbols in argument 'name'");
        }

        $query = "UPDATE university SET  name = :name, date = current_timestamp WHERE id=:id";
        $data = self::getInstance()->getConnection()->prepare($query);
        $data->execute(['id' => $id, 'name' => $name]);
    }

    /**
     * @param int|null $id
     * @param int|null $parent_id
     * @throws Exception
     */
    public function updateParentID(?int $id, ?int $parent_id)
    {
        if (intval($id)!==$id) {
            throw new Exception("INCORRECT INPUT: argument 'id' must be integer.");
        }
        if (intval($parent_id)!==$parent_id) {
            throw new Exception("INCORRECT INPUT: argument 'parent_id' must be integer.");
        }

        $query = "UPDATE university SET  parent_id = :parent_id, date = current_timestamp WHERE id=:id";
        $data = self::getInstance()->getConnection()->prepare($query);
        $data->execute(['id' => $id, 'parent_id' => $parent_id]);
    }

    /**
     * @param int|null $id
     * @throws Exception
     */
    public function deleteNode(?int $id)
    {
        if (intval($id)!==$id) {
            throw new Exception("INCORRECT INPUT: argument 'id' must be integer.");
        }

        $data = $this->returnData();

        foreach ($data as $item) {
            if ($item['id'] === $id) {
                $delete = self::getConnection()->prepare("DELETE FROM university WHERE id=:del_id");
                $delete->execute(['del_id' => $id]);
            }
            if ($item['parent_id'] === $id) {
                $this->deleteNode($item['id']);
                $del_id = $item['id'];
                $delete = self::getConnection()->prepare("DELETE FROM university WHERE id=:del_id");
                $delete->execute(['del_id' => $del_id]);
            }
        }
    }
}

echo DataBase::getInstance()->buildTree(1);
