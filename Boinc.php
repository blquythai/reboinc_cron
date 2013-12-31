<?php
require_once "../application/models/MGDatabase.php";
class Boinc {
    // Configurations
    public $boincCGI = 'http://rabbit.cs.pdx.edu/fishcounting/cgi-bin/cgi';
    public $boincUpload =
        'http://rabbit.cs.pdx.edu/fishcounting/cgi-bin/file_upload_handler';

    //public $operationsPerSecond = 1000; // benchmark of operations per second

    public function __construct($ip, $uid = null){
        $this->uid = 1;
        $this->ip = $ip;
    }

    // This function will pull data from a boinc server or multiple server
    // then return it to the client with a javascript file that act as a
    // BOINC client on OS level, this javascript will fetch BOINC Nacl App
    // from Metacaptcha server which was fetched from BOINC
    // and use the input from this function to execute on client browser.
    // When finished, the result will be submitted to Metacaptcha normally
    // in order for user to proceed.
    public function generateHash($Dc){

        //for_client is the array of frames that will be send to client
        // format:  associative array of form ['frameID'] => url;
        $for_client = array();
        //for_storing_db is array of containing results information
        //After client submit its work, the information in for_storing_db will be retrieved
        //to verify the client's work and submit the result back to BOINC
        //Format:
        //  array of result:
        //        result['boinc_info'] information of the result from the xml file
        //        result['known'] => array of known frame's ID
        //        result['unknown'] => array of unknown frame's ID
        $for_storing_db['type'] = 'boinc';
        $for_storing_db['results'] = array();
        for ($i = 0 ; $i < intval($Dc); $i++)
        {
//            $requestXml = file_get_contents(dirname($this->puzzleDir) .
//            '/Boinc/working_request_nacl.xml');
            $requestXml = file_get_contents('sched_request.xml');

            //$client = new Zend_Http_Client($this->boincCGI);
            //$response = $client->setRawData($requestXml, 'text/xml')
            //              ->request('POST')->getBody();

            //TEST: the working_task_response.xml in /Boinc is what we received.
//            $response = file_get_contents(dirname($this->puzzleDir) .
//            '/Boinc/working_task_response_nacl.xml');
            $response = file_get_contents('sched_reply.xml');
            // Add @ to ignore warning since $reponse is XML not HTML
            $xml = self::_loadXml($response, $useSimpleXml = true);

            // This can be used to make sure that the correct appname is received
            $appName = $xml->app->name;
            //        if($appName != 'fishcounting')
            //            echo "Wrong app from BOINC";
            $results = $xml->result;
            foreach ($results as $result)
            {
                $temp = array(); //a single element of the $for_storing_db['results']  array
                $temp['boinc_info'] = $result; //all the result's info in the XML file
                $temp['known'] = array(); //information about the know frames in the result
                $temp['unknown'] = array();//information about the uknown frames in the result
                //get workunit name of the result
                $wu_name = $result->wu_name;

                //from the wu name, query the file info
                //Note that input file name == wu name
                $file_info = $xml->xpath("//file_info[name='$wu_name']"); //TODO catch error (it might never happen)
                $url = $file_info[0]->url;
                //TODO verify md5 and file size (not really neccessary, would result in delay for client, )
                //download the file from boinc server
                $sub_puzzle = $this->download_page($url); //let's call it sub-puzzle

                //create an json object containg information about the resuls
                //and to store in the database
                $sub_puzzle = json_decode($sub_puzzle,$assoc = true );
                //create puzzle based on the file
                foreach ($sub_puzzle['unknowframes'] as $frame)
                {

                    $url  =  $sub_puzzle['url'].$frame['name'];
                    $for_client [$frame['frameID']] = $url;

                    $temp['unknown'][] =array( 'frameID' => $frame['frameID']);
                }

                foreach ($sub_puzzle['knowframes'] as $frame)//TODO correct the misspelling, just change to "known" and "unknown"
                {

                    $url  =  $sub_puzzle['url'].$frame['name'];
                    $for_client [$frame['frameID']] = $url;
                    $temp['known'][] = array( 'frameID' => $frame['frameID'],
                        'x' => $frame['x'],
                        'y' => $frame['y'],
                        'width' => $frame['width'],
                        'height' => $frame['height'],
                    );
                }



            }

        }
        //Put them into database so the output of this puzzle can be
        //searched/submitted to BOINC after finished computing
        $this->_updateServiceRecord($for_storing_db);


        //TODO write in in NACL

        $params = array();
//        //TODO(THAI) Get the necessary tasks and return it with the nacl to do the
//        //computation.
//        //TEST Get input file name of first workunit
//        $wu = reset($workunits);
//        $fileName = $wu['file_ref']['file_name'];
//        $fileInputUrl = $files[$fileName]['url'];
//
//        $params = array(
//            'content' => $this->_generateSolver(),
//            3 => $fileInputUrl,
//            2 => $this->_getManifest($appName),
//            1 => $wu['name']
//        );

        return $params;
    }
    // BOINC XMl file use & in the file without encoding it making XML parser
    // failed. Damn BOiNC! writing bad code.
    protected static function _loadXml($content, $useSimpleXml = false){
        $content = str_replace('&','&amp;', $content);
        if ($useSimpleXml != true)
        {
            return DOMDocument::loadXML($content);
        }
        else
        {
            return new SimpleXMLElement($content);
        }

    }

    protected function _generateSolver(){
        $file = file_get_contents($this->puzzleDir .'/boinc.js');
        return $file;
    }

    // TODO Get manifest file according to app name
    protected function _getManifest($appName = null){
        return
            'http://rabbit.cs.pdx.edu/headwinds_dev/test/get-manifest?project=fishcounting';
    }

    protected function _xmlToArray(DOMNodeList $nodeList){
        $row = array();
        foreach ($nodeList as $node) {
            if($node->childNodes && $node->childNodes->length > 1){
                $row[$node->nodeName] = $this->_xmlToArray($node->childNodes);
            } else
                $row[$node->nodeName] = $node->nodeValue;
        }
        return $row;
    }

    protected function _normalizeXmlDoc(DOMNodeList $nodeList){
        $files = array();
        foreach ($nodeList as $file) {
            $row = $this->_xmlToArray($file->childNodes);
            $files[$row['name']] = $row;
        }
        return $files;
    }

    protected function _updateServiceRecord($record){
        // Make the connection:
        $connection = new Mongo(DATABASE_HOST);
        $database   = $connection->selectDB(DATABASE_NAME);
        $database->authenticate(DATABASE_USER, DATABASE_PASSWORD);
        $collection = $database->selectCollection('nhan');

        if (!$connection) {
            trigger_error('Could not connect to MongoDB! ');
        }

        try {
            $collection->update(
                array("uid" => $this->uid),
                array('$set' => $record));
        } catch(MongoConnectionException $e) {
            die("Failed to connect to database ".$e->getMessage());
        } catch(MongoException $e) {
            die('Failed to insert data '.$e->getMessage());
        }
    }

    protected function _getServiceRecord(){
        // Make the connection:
        $connection = new Mongo(DATABASE_HOST);
        $database   = $connection->selectDB(DATABASE_NAME);
        $database->authenticate(DATABASE_USER, DATABASE_PASSWORD);
        $collection = $database->selectCollection('temp');

        if (!$connection) {
            trigger_error('Could not connect to MongoDB! ');
        }

        try {
            $cursor = $collection->find(array('uid' => $this->uid));
            return reset(iterator_to_array($cursor));
        } catch(MongoConnectionException $e) {
            die("Failed to connect to database ".$e->getMessage());
        } catch(MongoException $e) {
            die('Failed to insert data '.$e->getMessage());
        }
    }

    protected function _appendServiceRecord($record){
        // Make the connection:
        $connection = new Mongo(DATABASE_HOST);
        $database   = $connection->selectDB(DATABASE_NAME);
        $database->authenticate(DATABASE_USER, DATABASE_PASSWORD);
        $collection = $database->selectCollection('temp');

        if (!$connection) {
            trigger_error('Could not connect to MongoDB! ');
        }

        try {
            // Find old value
            $cursor = $collection->find(array('uid' => $this->uid));
            $service = reset(iterator_to_array($cursor));
            // Append new value
            foreach($record as $key => $value){
                // Incase the key is not available the script still works
                $record[$key] = @$service[$key] . $value;
            }
            // Update new value
            $collection->update(
                array("uid" => $this->uid),
                array('$set' => $record));
        } catch(MongoConnectionException $e) {
            die("Failed to connect to database ".$e->getMessage());
        } catch(MongoException $e) {
            die('Failed to insert data '.$e->getMessage());
        }
    }
    function download_page($path){
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