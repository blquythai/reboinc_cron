<?php
/*
 * Represent a result sent from BOINC. This is a life cycle of a result
 * First , a result is sent from BOINC in form of XML
 * Then metacaptcha will process the XML and fetch extra infomation specified in the XML
 * ( ie the XML contains link to the input data and specify how to submit the output)
 * Then a reduced version of the result is stored in the nacl_fish database
 * This can be retrieved by ObjectID
 * This Result class  represents the reduced version stored in the database
 */
require_once("Rect.php");
require_once("MongoSingleton.php");
class Result {

    /**
     * @throws Exception id doesn't exist
     * Initialize result object by retrieving the record in db with corresponding Objectid
     * @param $id
     *
     */

    public function __construct($id)
    {

        $this->db = MongoSingleton::getInstance();
        $this->_id = new MongoId("$id");
        $cursor = $this->db->findInCollection(MongoSingleton::$nacl_fish_collection, array('_id' => $this->_id ));
        $cursor = $this->db->findInCollection(MongoSingleton::$nacl_fish_collection,array('_id' => $this->_id ));
        if ($cursor->count()==0)
            throw new Exception("id doesn't exist");
        $this->document = $cursor->getNext();
    }
    //TODO: this assume that there only on result in a XML file. Handle multiple result later
    /**
     * @param $xml xml file sent from BOINC
     */

    /**
     * Verify the answer by comparing the known images in the document against the images in the answer
     * @param $answer array of this form "link_to_img"=> "array of rectangle containing the fish"
     * @return bool
     */
    public function verify($answer)
    {
        //extract known  images
        $knowns = $this->document['known'];
        try{
            foreach ($knowns as $known)
            {
                $url = $known['url'];
                //TODO : resolve the collision with Rect class of Fishy, change it to Rect 2 for now
                if (!array_key_exists($url , $answer) ||!Rect2::compareTwoRectArray($known['rects'], $answer["$url"]))
                    return false;
            }
        }
        catch (Exception $e)
        { return false;}

        return true;
    }

    /**
     * @throws Exception doesn't found unknown image in the result set
     * Call this function after the answer has been verified. it will store the answer of "unknown" images
     * and change the status by increasing its value by one
     * @param $answer array of this form "link_to_img"=> "array of rectangle containing the fish"
     */
    public function storeAnswerAndChangeStatus($answer)
    {
        //traverse the unknown images array
        $unknowns = $this->document['unknown'];//document['unknown'] is array of "link_to_img"=>[] (place holder)
        //index to the answer
        $updated_unknowns = array();
        foreach ($unknowns as $unknown)
        {
            $url = $unknown['url'];
            //use $link to index into $answer to retrieve the array of rectangles
            if (array_key_exists($url, $answer))
            {
                $unknown['rects'][] = $answer["$url"];
                $updated_unknowns []= $unknown;
            }
            else
            {
                echo $url;
                throw new Exception("doesn't found unknown image in the result set ".$url);
            }
        }
        $query = array ("_id" =>$this->_id);
        //update the unknown array in this result
        $update =array(
            '$set' => array ("unknown"=> $updated_unknowns),
            '$inc'=>array("status"=>1)
        );
//        var_dump($updated_unknowns);
        //change status to solve
        $this->db->updateCollection(MongoSingleton::$nacl_fish_collection, $query, $update);
    }
    //TODO: this assume that there only one result in a XML file. Handle multiple result later
    /**
     * @param $xml xml file sent from BOINC
     */
    public static function storeToDb($xml)
    {
        $xml = str_replace('&','&amp;', $xml);
        $xml = new SimpleXMLElement($xml);
        $result = $xml->result;
        //$for_storing_db is associative array of containing results information
        //After client submit its work, the information in for_storing_db will be retrieved
        //to verify the client's work and submit the result back to BOINC
        //Format:
        //  array of result:
        //        result['boinc_info'] content of the xml file (IGNORE FOR NOW)
        //        result['known'] => array of known frame's ID(for verification)
        //        result['unknown'] => array of unknown frame's ID

        $frames = array();
        //        $temp['boinc_info'] = $xml;
        $frames['known'] = array();
        $frames['unknown'] = array();
        //get workunit name of the result
        $wu_name = $result->wu_name;

        //from the wu name, query the file info
        $file_name = $xml->xpath("workunit[name='$wu_name']/file_ref/file_name");
        $file = $xml->xpath("//file_info[name='$file_name[0]']");

        //download the file from boinc server
        $input = self::curl_download($file[0]->url); //let's call it sub-puzzle
        //create an json object containing information about the result
        //and to store it into the database

        if (strlen($input) <10)
        {
            print $file[0]->url;
            die;
        }
        $input = json_decode($input,$assoc = true );

        //store the result into db
        $db= MongoSingleton::getInstance();
        $for_storing_db ['result'] = $xml->result[0];
        $for_storing_db ['workunit'] = $xml->workunit[0];
        $for_storing_db ['known'] = $input['known'];
        $for_storing_db ['unknown'] = $input['unknown'];
        $for_storing_db ['status'] = 0;
        //insert is
        $date = new DateTime();
        $for_storing_db['ts'] = $date->getTimestamp();
        $db->insertToCollection(MongoSingleton::$nacl_fish_collection,$for_storing_db );
    }

//    public static function storeToDb($xml)
//    {
//        $xml = str_replace('&','&amp;', $xml);
//        $xml = new SimpleXMLElement($xml);
//        $result = $xml->result;
//        //$for_storing_db is associative array of containing results information
//        //After client submit its work, the information in for_storing_db will be retrieved
//        //to verify the client's work and submit the result back to BOINC
//        //Format:
//        //  array of result:
//        //        result['boinc_info'] content of the xml file (IGNORE FOR NOW)
//        //        result['known'] => array of known frames
//        //        result['unknown'] => array of unknown frames
//
//        $frames = array();
//        //        $temp['boinc_info'] = $xml;
//        $frames['known'] = array();
//        $frames['unknown'] = array();
//        //get workunit name of the result
//        $wu_name = $result->wu_name;
//
//        //from the wu name, query the file info
//        $file_name = $xml->xpath("workunit[name='$wu_name']/file_ref/file_name");
//        $file = $xml->xpath("//file_info[name='$file_name[0]']");
//
//        //download the file from boinc server
//        $input = self::curl_download($file[0]->url); //let's call it sub-puzzle
//        //create an json object containing information about the result
//        //and to store it into the database
//        $input = json_decode($input,$assoc = true );
//
//
//        //store the result into db
//        $db= MongoSingleton::getInstance();
//        $for_storing_db ['result'] = $xml->result[0];
//        $for_storing_db ['workunit'] = $xml->workunit[0];
//        $for_storing_db ['known'] = $input['known'];
//        $for_storing_db ['unknown'] = $input['unknown'];
//        $for_storing_db ['status'] = 0;
//        //insert is
//        $date = new DateTime();
//        $for_storing_db['ts'] = $date->getTimestamp();
//        var_dump($for_storing_db);
//        $db->insertToCollection("nacl_fish",$for_storing_db );
//    }
    private static function curl_download($path){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$path);
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $retValue = curl_exec($ch);
        curl_close($ch);
        return $retValue;
    }
}