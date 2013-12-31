<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zac
 * Date: 11/15/13
 * Time: 3:06 PM
 * To change this template use File | Settings | File Templates.
 */

//contains the information for the rectangular containing the fish
class Rect
{
    public $x;
    public $y;
    public $width;
    public $height;
    function __construct( $x,$y, $width, $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }
    //assume that the native client return the rects in the same order the the same image
    static function compareTwoRectArray($rects1, $rects2)
    {
        $len1 = count($rects1);
        $len2 = count($rects2);

        if ( $len1 != $len2)
            return false;
        for ($i=0; $i < $len1; $i++)
            if (!self::compareTwoRects($rects1[$i], $rects2[$i]))
                return false;
        return true;
    }
    static function compareTwoRects($rect1, $rect2)
    {
        if ($rect1['x'] == $rect2['x'] && $rect1['y'] == $rect2['y'] && $rect1['width'] == $rect2['width'] && $rect1['height'] == $rect2['height'] )
            return true;
        else
            return false;
    }

}

/*
 * Test code
 */

//$str = '{"http://api.metacaptcha.com/public/images/278.jpg": [{"x": "84", "width": "121", "y": "165", "height": "48"},{"x": "60", "width": "78", "y": "258", "height": "31"},{"x": "26", "width": "97", "y": "272", "height": "39"}],"http://api.metacaptcha.com/public/images/98.jpg": [{"x": "39", "width": "58", "y": "44", "height": "23"},{"x": "61", "width": "118", "y": "273", "height": "47"},{"x": "0", "width": "210", "y": "92", "height": "84"}],"http://api.metacaptcha.com/public/images/111.jpg": [{"x": "104", "width": "60", "y": "84", "height": "24"},{"x": "6", "width": "216", "y": "103", "height": "87"}],"http://api.metacaptcha.com/public/images/286.jpg": [{"x": "154", "width": "75", "y": "204", "height": "30"},{"x": "60", "width": "134", "y": "163", "height": "54"},{"x": "5", "width": "144", "y": "253", "height": "58"}],"_id":"52866b5f11cd27b6052eae8e"}';
//$obj = json_decode($str, true);
//var_dump( Rect::compareTwoRectArray($obj["http://api.metacaptcha.com/public/images/278.jpg"], $obj["http://api.metacaptcha.com/public/images/278.jpg"]));
//var_dump(  Rect::compareTwoRectArray($obj["http://api.metacaptcha.com/public/images/278.jpg"], $obj["http://api.metacaptcha.com/public/images/98.jpg"]));
//var_dump(  Rect::compareTwoRectArray($obj["http://api.metacaptcha.com/public/images/98.jpg"], $obj["http://api.metacaptcha.com/public/images/278.jpg"]));
//var_dump(  Rect::compareTwoRectArray($obj["http://api.metacaptcha.com/public/images/98.jpg"], $obj["http://api.metacaptcha.com/public/images/98.jpg"]));
