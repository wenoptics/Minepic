<?php
class Minepic {
    // Constants
    const DEFAULT_NAME = 'Steve';
    const SKINS_FOLDER = 'skins';
    const RENDERED_SKINS_FOLDER = 'rederedskins';
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
    
    // Get full skin
    public function get_skin($username) {
        if ($this->check_premium($username) == true) {
            @$headers = get_headers("http://s3.amazonaws.com/MinecraftSkins/".$username.".png");
            if (@$headers[7] == 'Content-Type: image/png' || @$headers[7] == 'Content-Type: application/octet-stream') {
		$skin_img = imagecreatefrompng('https://s3.amazonaws.com/MinecraftSkins/'.$username.'.png');
		imagealphablending($skin_img, false);
		imagesavealpha($skin_img, true);
		imagepng($skin_img, './'.$this::SKINS_FOLDER.'/'.$username.'.png');
		return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    // Show rendered skin
    public function show_rendered_skin($username, $size = 256) {
	if ($this->img_exists($username, 'skin') == false) {
	    if ($this->get_skin($username) == false) {
		$skin_img = './'.$this::SKINS_FOLDER.'/Steve.png';
		return $this->render_skin($skin_img, $size);
	    } else {
		$skin_img = './'.$this::SKINS_FOLDER.'/'.$username.'.png';
		return $this->render_skin($skin_img, $size);
	    }
	} else {
	    $skin_img = './'.$this::SKINS_FOLDER.'/'.$username.'.png';
	    $ts_file = filemtime($skin_img);
	    if ( (time() - $ts_file) > $this::CACHE_TIME) {
		$this->get_skin($username);
		return $this->render_skin($skin_img, $size);
	    } else {
		return $this->render_skin($skin_img, $size);
	    }
	}
    }
    
    public function render_skin($skin_img, $skin_height = 256) {
	$image = imagecreatefrompng($skin_img);
	$scale = $skin_height / 32;
	$body_canvas = imagecreatetruecolor(16*$scale, 32*$scale);
	imagealphablending($body_canvas, false);
	imagesavealpha($body_canvas,true);
	$transparent = imagecolorallocatealpha($body_canvas, 255, 255, 255, 127);
	imagefilledrectangle($body_canvas, 0, 0, 16*$scale, 32*$scale, $transparent);
	// Head
	imagecopyresized($body_canvas, $image, 4*$scale, 0*$scale, 8, 8, 8*$scale, 8*$scale, 8, 8);
	// Body Front
	imagecopyresized($body_canvas, $image, 4*$scale, 8*$scale, 20, 20, 8*$scale, 12*$scale, 8, 12);
	// Right Arm (left on img)
	$r_arm = imagecreatetruecolor(4, 12);
	imagecopy($r_arm, $image, 0, 0, 44, 20, 4, 12);
	imagecopyresized($body_canvas, $r_arm, 0*$scale, 8*$scale, 0, 0, 4*$scale, 12*$scale, 4, 12);
	// Left Arm (right on img) by flipping right arm
	$l_arm = imagecreatetruecolor(4, 12);
	for ($x = 0; $x < 4; $x++) {
	    imagecopy($l_arm, $r_arm, $x, 0, 4 - $x - 1, 0, 1, 12);
	}
	imagecopyresized($body_canvas, $l_arm, 12*$scale,  8*$scale,  0,  0,  4*$scale,  12*$scale, 4,  12);
	// Right leg (left on img)
	$r_leg = imagecreatetruecolor(4, 20);
	imagecopy($r_leg, $image, 0, 0, 4, 20, 4, 12);
	imagecopyresized($body_canvas, $r_leg, 4*$scale, 20*$scale, 0, 0, 4*$scale, 12*$scale, 4, 12);
	// Left leg (right on img)
	$l_leg = imagecreatetruecolor(4, 20);
	for ($x = 0; $x < 4; $x++) {
	    imagecopy($l_leg, $r_leg, $x, 0, 4 - $x - 1, 0, 1, 20);
	}
	imagecopyresized($body_canvas, $l_leg, 8*$scale, 20*$scale,  0,  0,  4*$scale,  12*$scale, 4,  12);
	header('Content-Type: image/png');
	return imagepng($body_canvas);
    }
    
    // Create avatar from skin
    public function avatar($username, $size = 200) {
	if ($this->img_exists($username, 'skin') == false) {
	    if ($this->get_skin($username) == false) {
		$skin_img = './'.$this::SKINS_FOLDER.'/Steve.png';
		return $this->render_avatar($skin_img, $size);
	    } else {
		$skin_img = './'.$this::SKINS_FOLDER.'/'.$username.'.png';
		return $this->render_avatar($skin_img, $size);
	    }
	} else {
	    $skin_img = './'.$this::SKINS_FOLDER.'/'.$username.'.png';
	    $ts_file = filemtime($skin_img);
	    if ( (time() - $ts_file) > $this::CACHE_TIME) {
		$this->get_skin($username);
		return $this->render_avatar($skin_img, $size);
	    } else {
		return $this->render_avatar($skin_img, $size);
	    }
	}
    }
    
    // Render avatar (only head from skin image)
    public function render_avatar_old($skin_img, $size = 200, $with_helm = 0) {
	if ($size == NULL OR $size <= 0) { $size = 200; }
	// generate png from url/path
	$image = imagecreatefrompng($skin_img);
	$avatar = imagecreatetruecolor($size, $size);
	imagecopyresampled($avatar, $image, 0, 0, 8, 8, $size, $size, 8, 8);
	header('Content-Type: image/png');
	if ($with_helm == 1) {
	    $helm = imagecreatetruecolor($size, $size);
	    imagealphablending($helm, false);
	    imagesavealpha($helm,true);
	    imagecopyresampled($helm, $image, 0, 0, 40, 8, $size, $size, 8, 8);
	    // creating a cut resource 
	    $merge = imagecreatetruecolor($size, $size); 
	    // copying relevant section from background to the cut resource 
	    imagecopy($merge, $avatar, 0, 0, 0, 0, $size, $size); 
	    // copying relevant section from watermark to the cut resource 
	    imagecopy($merge, $helm, 0, 0, 0, 0, $size, $size); 
	    // insert cut resource to destination image 
	    imagecopymerge($avatar, $merge, 0, 0, 0, 0, $size, $size, 0);
	    return imagepng($merge);
	} else {
	    return imagepng($avatar);
	}
    }
    
    // Render avatar (only head from skin image)
    public function render_avatar($skin_img, $size = 200, $header = 1) {
	if ($size == NULL OR $size <= 0) { $size = 200; }
	// generate png from url/path
	$image = imagecreatefrompng($skin_img);
	$avatar = imagecreatetruecolor($size, $size);
	imagecopyresampled($avatar, $image, 0, 0, 8, 8, $size, $size, 8, 8);
	$helm = imagecreatetruecolor($size, $size);
	imagealphablending($helm, false);
	imagesavealpha($helm,true);
	imagecopyresampled($helm, $image, 0, 0, 40, 8, $size, $size, 8, 8);
	$no_helm = 0;
	if ($header == 1 ) { header('Content-Type: image/png'); }
	// Basic check for not-helm image
	for ($x=0;$x<8;$x++) {
	    for ($y=0;$y<8;$y++) {
		$color=imagecolorat($helm, $x, $y);
		$colors = imagecolorsforindex($helm, $color);
		if ($colors['alpha'] == 0) {
		    $no_helm++;
		}
	    }
	}
	// if all pixel haven't transparency
	if ($no_helm < 64) {
	    $merge = imagecreatetruecolor($size, $size); 
	    imagecopy($merge, $avatar, 0, 0, 0, 0, $size, $size); 
	    imagecopy($merge, $helm, 0, 0, 0, 0, $size, $size); 
	    imagecopymerge($avatar, $merge, 0, 0, 0, 0, $size, $size, 0);
	    return imagepng($merge); // return avatar with helm
	} else {
	    return imagepng($avatar); // return avatar without helm
	}
    }
    
    // Check if img exist
    private function img_exists($username, $filetype = 'skin') {
	if ($filetype == 'head') { $folder = $this::HEADS_FOLDER; }
	elseif ($filetype == 'rederedskin') { $folder = $this::RENDERED_SKINS_FOLDER; }
	else { $folder = $this::SKINS_FOLDER; }
        if(file_exists('./'.$folder.'/'.$username.'.png')) {
            return true;
        } else {
            return false;
        }
    }
}
?>
