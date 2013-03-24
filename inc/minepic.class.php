<?php
class Minepic {
    // Constants
    const DEFAULT_NAME = 'Steve';
    const SKINS_FOLDER = 'skins';
    const HEADS_FOLDER = 'heads';
    const DEFAULT_HEADS_SIZE = 200;
    const CACHE_TIME = 43200;
    
    // Generic function for cURL requests
    private function curl_request($address) {
        $request = curl_init();
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE); // for haspaid.jsp
        curl_setopt($request, CURLOPT_URL, $address);
        return curl_exec($request);
        curl_close($request);
    }
    
    // Check if username is premium
    public function check_premium($username) {
        return $this->curl_request('https://www.minecraft.net/haspaid.jsp?user='.$username);
    }
    
    public function save_skin($username) {
        if ($this->check_premium($username) == true) {
            @$headers = get_headers("http://s3.amazonaws.com/MinecraftSkins/".$username.".png");
            if (@$headers[7] == 'Content-Type: image/png' || @$headers[7] == 'Content-Type: application/octet-stream') {
		$skin_img = imagecreatefrompng('https://s3.amazonaws.com/MinecraftSkins/'.$username.'.png');
		imagepng($skin_img, './'.$this::SKINS_FOLDER.'/'.$username.'.png');
		return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function get_head($username, $only_save = 0, $size = NULL) {
        if ($this->check_premium($username) == 'true') {
	    if ($size == NULL) { $size = $this::DEFAULT_HEADS_SIZE; }
	    else {
		$size = intval($size);
		if ($size == 0) { $size = $this::DEFAULT_HEADS_SIZE; }
	    }
            @$headers = get_headers("http://s3.amazonaws.com/MinecraftSkins/".$username.".png");
	    if (@$headers[7] == 'Content-Type: image/png' || @$headers[7] == 'Content-Type: application/octet-stream') {
		$skin_img = imagecreatefrompng('https://s3.amazonaws.com/MinecraftSkins/'.$username.'.png');
		$skin_img2 = $skin_img;
		$canvas = imagecreatetruecolor($size, $size);
		imagecopyresampled($canvas, $skin_img, 0, 0, 8, 8, $size, $size, 8, 8);
		if ($only_save == 1) {
		    imagepng($canvas, './'.$this::HEADS_FOLDER.'/'.$username.'.png');
		    return true;
		}
		else {
		    $canvas_save = imagecreatetruecolor($size, $size);
		    imagecopyresampled($canvas_save, $skin_img2, 0, 0, 8, 8, $size, $size, 8, 8);
		    imagepng($canvas_save, './'.$this::HEADS_FOLDER.'/'.$username.'.png');
		    return imagepng($canvas);
		}
	    } else {
		if ($only_save == 1) { return false; }
		else {
		    if ($this->img_exists('Steve', 'head')) {
			$skin_img = imagecreatefrompng('https://s3.amazonaws.com/MinecraftSkins/char.png');
			$canvas = imagecreatetruecolor($size, $size);
			imagecopyresampled($canvas, $skin_img, 0, 0, 8, 8, $size, $size, 8, 8);
			return imagepng($canvas);
		    }
		}
	    }
        } else {
	    if ($only_save == 1) { return false; }
	    else {
		if ($this->img_exists('Steve', 'head')) {
		$skin_img = imagecreatefrompng('https://s3.amazonaws.com/MinecraftSkins/char.png');
		$canvas = imagecreatetruecolor($size, $size);
		imagecopyresampled($canvas, $skin_img, 0, 0, 8, 8, $size, $size, 200, 200);
		return imagepng($canvas);
		}
	    }
        }
    }
    
    public function get_cache($username, $filetype = 'skin', $size = 200) {
	if ($filetype == 'head') { $folder = $this::HEADS_FOLDER; }
	else { $folder = $this::SKINS_FOLDER; }
	$size = intval($size);
	$img_path = './'.$folder.'/'.$username.'.png';
	$img_info = getimagesize($img_path);
	$img = imagecreatefrompng($img_path);
	if ($size != 200 AND $size != 0) {
	    $canvas = imagecreatetruecolor($size, $size);
	    imagecopyresampled($canvas, $img, 0, 0, 0, 0, $size, $size, $img_info[0], $img_info[1]);
	    return imagepng($canvas);
	} else {
	    return imagepng($img);
	}
    }
    
    public function show_head($username, $size = 200) {
	header('Content-Type: image/png');
	if ($this->img_exists($username, 'head') == false) {
	    return $this->get_head($username, 0, $size); 
	} else {
	    // Cache Control
	    $ts_file = filemtime('./'.$this::HEADS_FOLDER.'/'.$username.'.png');
	    if ( (time() - $ts_file) > $this::CACHE_TIME) {
		return $this->get_head($username, 0, $size);
	    } else {
		return $this->get_cache($username, 'head', $size);
	    }
	}
    }  

    private function img_exists($username, $filetype = 'skin') {
	if ($filetype == 'head') { $folder = $this::HEADS_FOLDER; }
	else { $folder = $this::SKINS_FOLDER; }
        if(file_exists('./'.$folder.'/'.$username.'.png')) {
            return true;
        } else {
            return false;
        }
    }
}
?>
