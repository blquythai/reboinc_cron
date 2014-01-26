<?php
require_once "../Rect.php";

require_once "../Result.php";
$db =MongoSingleton::getInstance();
$str = '{"http://api.metacaptcha.com/public/images/278.jpg": [{"x": "99", "width": "121", "y": "165", "height": "48"},{"x": "60", "width": "78", "y": "258", "height": "31"},{"x": "26", "width": "97", "y": "272", "height": "39"}]}';
$update = array('$set'=>json_decode($str,true));
$db->updateCollection("nacl_fish",array('_id' => new MongoId("5288fcda11cd27b903416ffc")), $update);
$doc =  $db->findInCollection("nacl_fish",array('_id' => new MongoId("5288fcda11cd27b903416ffc")));
var_dump($doc->getNext());
//$str = '{"http://api.metacaptcha.com/public/images/278.jpg": [{"x": "84", "width": "121", "y": "165", "height": "48"},{"x": "60", "width": "78", "y": "258", "height": "31"},{"x": "26", "width": "97", "y": "272", "height": "39"}],"http://api.metacaptcha.com/public/images/98.jpg": [{"x": "39", "width": "58", "y": "44", "height": "23"},{"x": "61", "width": "118", "y": "273", "height": "47"},{"x": "0", "width": "210", "y": "92", "height": "84"}],"http://api.metacaptcha.com/public/images/111.jpg": [{"x": "104", "width": "60", "y": "84", "height": "24"},{"x": "6", "width": "216", "y": "103", "height": "87"}],"http://api.metacaptcha.com/public/images/286.jpg": [{"x": "154", "width": "75", "y": "204", "height": "30"},{"x": "60", "width": "134", "y": "163", "height": "54"},{"x": "5", "width": "144", "y": "253", "height": "58"}],"_id":"52866b5f11cd27b6052eae8e"}';
//$obj = json_decode($str, true);
//var_dump( Rect::compareTwoRectArray($obj["http://api.metacaptcha.com/public/images/278.jpg"], $obj["http://api.metacaptcha.com/public/images/278.jpg"]));
//var_dump(  Rect::compareTwoRectArray($obj["http://api.metacaptcha.com/public/images/278.jpg"], $obj["http://api.metacaptcha.com/public/images/98.jpg"]));
//var_dump(  Rect::compareTwoRectArray($obj["http://api.metacaptcha.com/public/images/98.jpg"], $obj["http://api.metacaptcha.com/public/images/278.jpg"]));
//var_dump(  Rect::compareTwoRectArray($obj["http://api.metacaptcha.com/public/images/98.jpg"], $obj["http://api.metacaptcha.com/public/images/98.jpg"]));

//var_dump($obj['http://api.metacaptcha.com/public/images/278.jpg'][0]);
//used id to look up in data base for the result
//retrieve the result from database

//get know get link of know images (class)

//use them to verify against the image submitted

//if doens't match
//if match, insert the result into "unknown" field and change the status
