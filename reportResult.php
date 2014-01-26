<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zac
 * Date: 12/1/13
 * Time: 6:49 PM
 * To change this template use File | Settings | File Templates.
 */
require "Result.php";
require "Helper.php";
$report_template = file_get_contents("xml/report.xml");
$upload_request_template = file_get_contents("xml/upload_request_template.xml");
$upload_template = file_get_contents("xml/upload_template.xml");

$upload_handler_url = "metacaptcha.com/final_cgi/file_upload_handler";

//select all result that has been computed by user (status > 0)
$mongo = MongoSingleton::getInstance();
$query = array("status" => array('$gt'=>0));
$cursor =$mongo->findInCollection(MongoSingleton::$nacl_fish_collection, $query);

if ($cursor->count()<=0)
{
    throw new Exception("No result to process");
    die;
}
foreach ( $cursor as $doc)
{
    //$doc = $cursor->getNext();

    //submit each record individually
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
    $xml = str_replace('&','&amp;', $report_template);
    $xml = new SimpleXMLElement($xml);

    //change result name
    $xml->result->name =$result['name'];
    $file_info_xml = $xml->result->file_info;

    //change file_info in the template
    $file_info_xml->name = $result['file_ref']['file_name'];
    $nbytes = strlen($data);
    $md5 =md5($data);
    $file_info_xml->nbytes = $nbytes;
    $file_info_xml->md5_cksum = $md5;
    //report the result with the scheduler
    $response = upload_xml_raw("http://metacaptcha.com/final_cgi/cgi", $xml->asXML());

    //send upload request to BOINC
    $xml = new SimpleXMLElement($upload_request_template);
    $xml->get_file_size = $file_info_xml->name;
    $response =   upload_xml_raw($upload_handler_url,$xml->asXML());
    $xml = new SimpleXMLElement($response);
    if ($xml->status != 0)
    {
        throw new Exception("Fail to request upload handler:\n".$xml->asXML());
    }
    //if the request is granted, upload the answer
    $upload = $upload_template.$file_info_xml->asXML().$file_info_xml->nbytes->asXML()
        .$file_info_xml->md5_cksum->asXML()
        ."\n<data>\n".$data; //the new line is crucial
    $response = upload_xml_raw($upload_handler_url, $upload);
    $xml = new SimpleXMLElement($response);
    if ($xml->status != 0)
    {
        throw new Exception("Fail to request upload handler:\n".$xml->asXML());
    }
    //delete the submitted result from database;
    $id = (string)$doc['_id'];
    $mongo->remove(MongoSingleton::$nacl_fish_collection, array(
        '_id' => new MongoId($id)));
}
