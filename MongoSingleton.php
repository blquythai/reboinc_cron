<?php
class MongoSingleton {
    static $inst = null;
    protected $db = null;
    protected $conn = null;
    public static $nacl_fish_collection = "nacl_fish_verify";

    public static function getInstance(){
        if(self::$inst === null){
            self::$inst = new MongoSingleton();
        }
        return self::$inst;
    }

    private function __construct(){
//        $config = new Zend_Config_Ini('config.ini', APPLICATION_ENV);
        if (!isset($db))
            $db = "SpamStatistics";
        $this->connection = new Mongo("mongodb://root:bees@ox.cs.pdx.edu/SpamStatistics");
        $this->db = $this->connection->$db;
    }

    public function remove($name, $query)
    {
        $collection = $this->db->selectCollection($name);
        $collection->remove($query);
    }
    public function selectCollection($name){
        $collection = $this->db->selectCollection($name);
        if(!$collection){
            throw new Exception("Could not select collection ");
        }
        return $collection;
    }
    public function insertToCollection($name,$document)
    {
        $collection = $this->db->selectCollection($name) ;
        $collection->insert($document);
    }
    public function findInCollection($name, $query)
    {
        $collection = $this->db->selectCollection($name);
        return $collection->find($query);
    }
    public function updateCollection($name, $query, $update,
                                     $options = array('upsert' => false,'multi' => false)){
        $collection = $this->selectCollection($name);
        try{
            $collection->update( $query, $update, $options);
        } catch(MongoConnectionException $e) {
            throw new Exception("Failed to connect to database ".$e->getMessage());
        } catch(MongoException $e) {
            throw new Exception('Failed to update data '.$e->getMessage());
        }

        return $collection;
    }

    /**
     * This method require the node to have its own _id => MongoId() before
     * saving
     */
    public function saveToCollection($name, $node, $options = array()){
        $collection = $this->selectCollection($name);
        try {
            $collection->save($node, $options);
        } catch (MongoException $e){
            throw new Exception("Failed to insert data\n".$e->getMessage());
        }
    }
}
