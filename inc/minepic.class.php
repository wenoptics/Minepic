<?php
class Minepic {
    // Constants
    const DEFAULT_NAME = 'Steve';
    const SKINS_FOLDER = 'skins';
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
	$username = str_replace('.png', NULL, $username);
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
	$avatar = $this->render_avatar($skin_img, 8, 0);
	imagecopyresized($body_canvas, $avatar, 4*$scale, 0*$scale, 0, 0, 8*$scale, 8*$scale, 8, 8);
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
	$username = preg_replace("#\?.*#", NULL, $username); // for mybb
	$username = str_replace('.png', NULL, $username);
	if ($this->img_exists($username) == false) {
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
    public function render_avatar($skin_img, $size = 200, $header = 1) {
	if ($size == NULL OR $size <= 0) { $size = 200; }
	// Default stdDev
	$def_stddev = 0.2;
	/*if ($size < 64) { $def_stddev = 0.1; }
	else { $def_stddev = 0.2; }*/
	// generate png from url/path
	@$image = imagecreatefrompng($skin_img);
	@imagealphablending($image, false);
	@imagesavealpha($image, true);
	$avatar = imagecreatetruecolor($size, $size);
	@imagecopyresampled($avatar, $image, 0, 0, 8, 8, $size, $size, 8, 8);
	// Check for helm image
	$helm_check = imagecreatetruecolor($size, $size);
	imagealphablending($helm_check, false);
	imagesavealpha($helm_check, true);
	$transparent = imagecolorallocatealpha($helm_check, 255, 255, 255, 127);
	imagefilledrectangle($helm_check, 0, 0, 8, 8, $transparent);
	@imagecopyresampled($helm_check, $image, 0, 0, 40, 8, 8, 8, 8, 8);
	for ($x=0;$x<8;$x++) {
	    for ($y=0;$y<8;$y++) {
		$color=imagecolorat($helm_check, $x, $y);
		$colors = imagecolorsforindex($helm_check, $color);
		$all_red[] = $colors['red'];
		$all_green[] = $colors['green'];
		$all_blue[] = $colors['blue'];
		$all_alpha[] = $colors['alpha'];
	    }
	}
	// mean value for each color
	$mean_red = array_sum($all_red) / 64;
	$mean_green = array_sum($all_green) / 64;
	$mean_blue = array_sum($all_blue) / 64;
	$mean_alpha = array_sum($all_alpha) / 64;
	for($i=0;$i<64;$i++) {
	    $devs_red[] = pow($all_red[$i] - $mean_red, 2);
	    $devs_green[] = pow($all_green[$i] - $mean_green, 2);
	    $devs_blue[] = pow($all_blue[$i] - $mean_blue, 2);
	}
	// stddev for each color
	$stddev_red = sqrt(array_sum($devs_red) / 64);
	$stddev_green = sqrt(array_sum($devs_green) / 64);
	$stddev_blue = sqrt(array_sum($devs_blue) / 64);
	// if all pixel have transparency or the colors aren't the same
	if ( ( ($stddev_red > $def_stddev AND $stddev_green > $def_stddev) OR 
		($stddev_red > $def_stddev AND $stddev_blue > $def_stddev) OR 
		($stddev_green > $def_stddev AND $stddev_blue > $def_stddev) ) OR 
		($mean_alpha == 127) ) {
	    $helm = imagecreatetruecolor($size, $size);
	    imagealphablending($helm, false);
	    imagesavealpha($helm, true);
	    $transparent = imagecolorallocatealpha($helm, 255, 255, 255, 127);
	    imagefilledrectangle($helm, 0, 0, $size, $size, $transparent);
	    @imagecopyresampled($helm, $image, 0, 0, 40, 8, $size, $size, 8, 8);
	    $merge = imagecreatetruecolor($size, $size); 
	    imagecopy($merge, $avatar, 0, 0, 0, 0, $size, $size); 
	    imagecopy($merge, $helm, 0, 0, 0, 0, $size, $size); 
	    imagecopymerge($avatar, $merge, 0, 0, 0, 0, $size, $size, 0);
	    if ($header == 1 ) { 
		header('Content-Type: image/png'); 
		return imagepng($merge);
	    } else {
		return $merge;
	    }
	     // return avatar with helm
	} else {
	    if ($header == 1 ) { 
		header('Content-Type: image/png'); 
		return imagepng($avatar); // return avatar without helm
	    } else {
		return $avatar;
	    }
	}
    }
    public function download_skin($username) {
	if (!$this->img_exists($username)) {
	    $username = 'Steve';   
	}
	$image = imagecreatefrompng('./'.$this::SKINS_FOLDER.'/'.$username.'.png');
	imagealphablending($image, true);
	imagesavealpha($image, true);
	header('Content-Disposition: Attachment;filename='.$username.'.png'); 	 
	header('Content-type: image/png');
	return imagepng($image);
    }
    
    // Get a random avatar from saved skins
    public function random_avatar($size = 200) {
	$all_skin = scandir($this::SKINS_FOLDER);
	$rand = rand(2, count($all_skin));
	$username = str_replace(".png", NULL, $all_skin[$rand]);
	header('Content-Disposition: inline; filename="'.$username.'.png";');
	return $this->avatar($username, $size);
    }
    
    public function update($username) {
	if ($this->get_skin($username) == TRUE) {
	    return $this->avatar($username);
	} else {
	    return $this->avatar($this::DEFAULT_NAME);
	}
    }
    
    // Check if img exist
    private function img_exists($username) {
        if(file_exists('./'.$this::SKINS_FOLDER.'/'.$username.'.png')) {
            return true;
        } else {
            return false;
        }
    }
}
?>
