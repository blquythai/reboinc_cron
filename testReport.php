<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zac
 * Date: 12/1/13
 * Time: 6:49 PM
 * To change this template use File | Settings | File Templates.
 */
require "Result.php";
require "helper.php";
$ts = getTS();
//find all unknown in the datase
//select all id
$mongo = MongoSingleton::getInstance();

$query = array("status" => array('$gt'=>0));
$cursor =$mongo->findInCollection(MongoSingleton::$nacl_fish_collection, $query);

if ($cursor->count()<=0)
{
    throw new Exception("No result to process");
    die;
}
//foreach ( $cursor as $doc)
//{
    $doc = $cursor->getNext();

    //for each record
    $result = $doc['result'];
    //prepare the answer to submit to BOINC
    $unknowns = $doc['unknown'];
    $answer = array(); //answer to be submitted to BOINC
    foreach ($unknowns as $unknown)
    {
        $frame =array();
        //for each unknown image in the result
        //find the correct result
        $frame['frameID'] = $unknown['frameID'];
        $rects = $unknown['rects'];
        $rects = processResult($rects); // choose the most correct result( based of frequency)
        $frame['rects'] = $rects;
        $answer[] = $frame;
    }
    $data = json_encode($answer);
    //load report template
    $xml = file_get_contents("xml/report.xml");
    $xml = str_replace('&','&amp;', $xml);
    $xml = new SimpleXMLElement($xml);

    //change result name
    $xml->result->name =$result['name'];
    $file_info = $xml->result->file_info;

    //change file_info
    $file_info->name = $result['file_ref']['file_name'];

    $data = "vbafdsfdsfdsfdsfdsds";
    $nbytes = strlen($data);
    $md5 =md5($data);
    $file_info->nbytes = $nbytes;
    $file_info->md5_cksum = $md5;
    $xml = $xml->asXML();
    //submit to BOINC
    //upload to scheduler
    $response = upload_xml_raw("http://metacaptcha.com/final_cgi/cgi", $xml);
    //upoad to BOINC

//    $xml = new SimpleXMLElement("upload_template.xml");
     $upload = file_get_contents("xml/upload_template.xml");
    $upload.= $file_info->asXML().$file_info->nbytes->asXML()
        .$file_info->md5_cksum->asXML()
        ."<data>".$data;
//    file_put_contents("tmp1", $upload);
    upload_xml_raw("metacaptcha.com/final_cgi/file_upload_handler",$upload); //TODO report missing tag -1 (fix it later)
//    $xml->data = $data;
//    echo $xml->asXML();
//    file_put_contents("tmp",$response);


//        <data_server_request>
//    <core_client_major_version>1</core_client_major_version>
//    <core_client_minor_version>1</core_client_minor_version>
//        <core_client_release>1</core_client_release>
//    <file_upload>
//    <file_info>
//       ...
//    <xml_signature>
//       ...
//    </xml_signature>
//    </file_info>
//    <nbytes>x</nbytes>
//    <md5_cksum>x</md5_cksum>
//    <offset>x</offset>
//    <data>
//}
        $te = getTS();
        $current = xdebug_memory_usage();
        $peak  = xdebug_peak_memory_usage();
        $data = $ts.','.$te.','.$peak.','.$current."\n";
    //remove current record
$id = (string)$doc['_id'];
$mongo->remove(MongoSingleton::$nacl_fish_collection, array(
    '_id' => new MongoId($id)));
file_put_contents("verify.csv",$data,FILE_APPEND);
/*
 * Process the array of results to return the best result (in term of occurrance)
 *
 */
function processResult($array)
{

    $hash_table = array();
    foreach ( $array as $ele)
    {
        $key = json_encode($ele);
        if (!array_key_exists($key , $hash_table))
        {
            $hash_table [$key] = 1;
        }
        else
            ++$hash_table[$key];
    }
    $return = "";
    $max =0;
    while (list($key, $value) = each($hash_table))
    {
        if ($value > $max)
        {
            $max = $value;
            $return = $key;
        }
    }
    return  $return;
}


