<?php
include ('./inc/minepic.class.php');
$minepic = new Minepic();
$ex_uri = explode("/", $uri);
$dimensions = intval($ex_uri[1]);
switch ($ex_uri[0]) {
    case 'avatar':
	if ($dimensions != 0 AND @$ex_uri[2] != '') {
	    echo $minepic->avatar($ex_uri[2], $dimensions);
	} else {
	    if (is_string($ex_uri[1]) AND $ex_uri[1] != '') {
		echo $minepic->avatar($ex_uri[1]);
	    } else {
		echo $minepic->avatar('Steve');
	    }
	}
    break;
    case 'random':
	header( 'Cache-Control: no-store, no-cache, must-revalidate' ); 
	header( 'Cache-Control: post-check=0, pre-check=0', false ); 
	header( 'Pragma: no-cache' ); 
	if ($dimensions != 0 AND $dimensions > 0) {
	    echo $minepic->random_avatar($dimensions);
	} else {
	    echo $minepic->random_avatar();
	}
    break;
    case 'skin':
	if ($dimensions != 0 AND @$ex_uri[2] != '') {
	    echo $minepic->show_rendered_skin($ex_uri[2], $dimensions);
	} else {
	    if (is_string($ex_uri[1]) AND $ex_uri[1] != '') {
		echo $minepic->show_rendered_skin($ex_uri[1]);
	    } else {
		echo $minepic->show_rendered_skin('Steve');
	    }
	}
    break;
    case 'download':
	echo $minepic->download_skin($ex_uri[1]);
    break;
    default:
	header('Location: http://minepic.org/');
    break;
}
?>