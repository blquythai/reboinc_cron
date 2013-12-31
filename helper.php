<?php
function getTS()
{
//        $date = new DateTime();
//        $ts = $date->getTimestamp();
    $ts = round(microtime(true) * 1000);
    return $ts;
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
function upload_xml($url, $xml_path)
{
    $handle = fopen($xml_path, "r");
    $XPost = fread($handle, filesize($xml_path));
    $ch = curl_init(); // initialize curl handle
    curl_setopt($ch, CURLOPT_VERBOSE, 1); // set url to post to
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 4s
    curl_setopt($ch, CURLOPT_POSTFIELDS, $XPost); // add POST fields
    curl_setopt($ch, CURLOPT_POST, 1);
    $result = curl_exec($ch); // run the whole process
    if (empty($result)) {
        // some kind of an error happened
        die(curl_error($ch));
        curl_close($ch); // close cURL handler
    } else {


        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        $body = substr($result, $header_size);
    }
    return $body;
}
function upload_xml_raw($url, $xml)
{
    $XPost = $xml;
    $ch = curl_init(); // initialize curl handle
    curl_setopt($ch, CURLOPT_VERBOSE, 1); // set url to post to
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 4s
    curl_setopt($ch, CURLOPT_POSTFIELDS, $XPost); // add POST fields
    curl_setopt($ch, CURLOPT_POST, 1);
    $result = curl_exec($ch); // run the whole process
    if (empty($result)) {
        // some kind of an error happened
        die(curl_error($ch));
        curl_close($ch); // close cURL handler
    } else {


        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        $body = substr($result, $header_size);
    }
    return $body;
}