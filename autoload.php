<?php
function __autoload($class_name) {
    include '/var/www/boi/application/models/'.$class_name . '.php';
}