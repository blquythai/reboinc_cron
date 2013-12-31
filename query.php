<?php
//class MongoSingleton {
//    static $inst = null;
//    protected $db = null;
//    protected $conn = null;
//
//    public static function getInstance(){
//        if(self::$inst === null){
//            self::$inst = new MongoSingleton();
//        }
//        return self::$inst;
//    }
//
//    private function __construct(){
////        $config = new Zend_Config_Ini('config.ini', APPLICATION_ENV);
//        if (!isset($db))
//            $db = "SpamStatistics";
//        $this->connection = new Mongo("mongodb://root:bees@ox.cs.pdx.edu/SpamStatistics");
//        $this->db = $this->connection->$db;
//    }
//
//    public function selectCollection($name){
//        $collection = $this->db->selectCollection($name);
//        if(!$collection){
//            throw new Exception("Could not select collection ");
//        }
//        return $collection;
//    }
//    public function findInCollection($name, $query)
//    {
//        $collection = $this->db->selectCollection($name);
//        return $collection->find($query);
//    }
//    public function updateCollection($name, $query, $update,
//                                     $options = array('upsert' => false,'multi' => false)){
//        $collection = $this->selectCollection($name);
//        try{
//            $collection->update( $query, $update, $options);
//        } catch(MongoConnectionException $e) {
//            throw new Exception("Failed to connect to database ".$e->getMessage());
//        } catch(MongoException $e) {
//            throw new Exception('Failed to update data '.$e->getMessage());
//        }
//
//        return $collection;
//    }
//
//    /**
//     * This method require the node to have its own _id => MongoId() before
//     * saving
//     */
//    public function saveToCollection($name, $node, $options = array()){
//        $collection = $this->selectCollection($name);
//        try {
//            $collection->save($node, $options);
//        } catch (MongoException $e){
//            throw new Exception("Failed to insert data\n".$e->getMessage());
//        }
//    }
//}

class Fishy_Generator {
    public function __construct(){
        $this->mongo = MongoSingleton::getInstance();

    }

    /*query the database for "unsolved" work unit(WU) and return the classifiers as well as the images for client to solve
        @return return associative array $array which has two fields
            $array['str'] input for native clientin this format cascadefile\nlinktoimages\nlinktoimages
            $array['_id']: Object ID of current work unit (this will be used to retrieve the WU when user submit the answer
                so that the answer can be verified)
    */

    public function generate()
    {
        $cursor = $this->mongo->findInCollection("nacl_fish",array ('status'=>'unsolved'));
        if ($cursor->count() <=0)
            $cursor = $this->mongo->findInCollection("nacl_fish",array ('status'=>'solved'));
        $doc = $cursor->getNext();
        $str = $this->result_to_str($doc);
        $array = array();
        $array['str'] = $str;
        $array['_id'] = "".$doc['_id'];
        var_dump($array);
        return $array;
    }

    /*
     * Used in generate function to create  a string  from a record in nacl_fish
     * @param : array represent a record
     * @return : string containing classifier, and images
     */
    private function result_to_str( $doc)
    {
        $str = array();
        //TODO: classifier should be include in the result, not hardcoded
        $classifier = "//api3.test.metacaptcha.com/public/nacl/abcde/cascade.xml";
        $str [] = $classifier;

        $knows  = $doc['known'];
        foreach ($knows as $key =>$value)
            $str [] = $key;
        $unknows  = $doc['unknown'];
        foreach ($unknows as $key =>$value)
            $str [] = $key;
        $str = implode("\n", $str);
        return $str;
    }

}

$str = 'http://api.metacaptcha.com/public/images/278.png';
echo $str;
