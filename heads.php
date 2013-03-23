<?php
include ('./inc/minepic.class.php');
$uri = $_SERVER['REQUEST_URI'];
$uri = trim($uri, "/");
$ex_uri = explode("/", $uri);
$dimensions = intval($ex_uri[1]);
$minepic = new Minepic();
if ($dimensions != 0 AND $ex_uri[2] != '') {
   echo $minepic->show_head($ex_uri[2], $dimensions);
} else {
    if (is_string($ex_uri[1]) AND $ex_uri[1] != '') {
	echo $minepic->show_head($ex_uri[1]);
    } else {
	echo $minepic->show_head('Steve');
    }
}
?>
