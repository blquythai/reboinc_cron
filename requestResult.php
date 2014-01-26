<?php
/**
 * Test request a workuit from BOINC
 */
require "Result.php";
require_once "Helper.php";
$ts = getTS();
$number_of_workunit = 1;
$url  = "http://metacaptcha.com/final_cgi/cgi";
$i=0;

for ($i = 0 ; $i < $number_of_workunit ; $i++)
{
   
    $xml =upload_xml($url, "xml/sched_request_metacaptcha.com_final.xml");
//    echo $xml.'<br/>';
    if (strpos($xml, "Project has no tasks available"))
    {
        echo $xml;
        echo "out of workunit";
        die;
    }

    else if (!strpos($xml, "http://metacaptcha.com/final/download/worker"))
    {
        echo $xml;
    }
	else Result::storeToDb($xml);
//    sleep(0.1);
    /*benchmark*/
//        $te = getTS();
//        $current = xdebug_memory_usage();
//        $peak  = xdebug_peak_memory_usage();
//        $data = $ts.','.$te.','.$peak.','.$current."\n";
//        file_put_contents("getwork.csv",$data,FILE_APPEND);
   
}
echo $i;
