<?php
class Minepic {
    // Constants
    const DEFAULT_NAME          = 'Steve';  // Default skin name
    const SKINS_FOLDER          = 'skins';  // Skins folder name
    const DEFAULT_HEADS_SIZE    = 256;      // Default avatar size in pixels if not specified
    const DEFAULT_SKINS_SIZE    = 256;      // Default avatar size in pixels if not specified
    const CACHE_TIME            = 43200;    // Image caching time (in seconds)
    const DEFAULT_STDDEV        = 0.2;      // Default standard deviation value for helm checks
    const PROFILE_URL           = 'https://api.mojang.com/profiles/page/1';
    
    // Variables
    public $username, $idmc;
    private $curlresp, $curlinfo;
    
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
    
    //  Execute a POST request with JSON data
    private function curl_json($url, $array) {
        $request = curl_init();
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_HTTPHEADER , array('Content-Type: application/json'));
        curl_setopt($request, CURLOPT_POST, TRUE);
        curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($array));
        curl_setopt($request, CURLOPT_URL, $url);
        $response = curl_exec($request);
        $this->curlinfo = curl_getinfo($request);
        curl_close($request);
        $rjson = json_decode($response);
        if ($this->curlinfo['http_code'] == '200') {
            $this->curlresp = $rjson;
            return TRUE;
        } else {
            $this->curlresp = NULL;
            return FALSE;
        }
    }
    
    // Check if username is premium
    public function check_premium($username) {
        if ($this->curl_request('https://www.minecraft.net/haspaid.jsp?user='.$username)  == 'true') {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    // Check if is a valid username
    private function check_username($username) {
        if (preg_match('#[^a-zA-Z0-9_]+#', $username)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    public function get_user_info($username) {
        if ($this->check_username($username) === TRUE) {
            $p_array['agent'] = 'Minecraft';
            $p_array['name'] = $username;
            if ($this->curl_json(self::PROFILE_URL, $p_array) === TRUE) {
                if (isset($this->curlresp->profiles[0]->name)) {
                    $this->username = $this->curlresp->profiles[0]->name;
                    $this->idmc = $this->curlresp->profiles[0]->id;
                    return TRUE;
                }
                else return FALSE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
    
    // Get full skin
    public function get_skin($username) {
        if ($this->get_user_info($username) === TRUE) {
            @$headers = get_headers("http://s3.amazonaws.com/MinecraftSkins/".$this->username.".png");
            if (@$headers[7] == 'Content-Type: image/png' || @$headers[7] == 'Content-Type: application/octet-stream') {
		$skin_img = imagecreatefrompng('https://s3.amazonaws.com/MinecraftSkins/'.$this->username.'.png');
		imagealphablending($skin_img, false);
		imagesavealpha($skin_img, true);
		imagepng($skin_img, './'.self::SKINS_FOLDER.'/'.$username.'.png');
		return true;
            } else {
		$this->get_steve($username);
                return true;
            }
        } else {
	    // if the requested username is not premium, create a skin image like 'Steve' (to speed up requests)
	    $this->get_steve($username);
            return true;
        }
    }
    
    // Get default (Steve) skin
    public function get_steve($username = NULL) {
	if ($username == NULL) $username = self::DEFAULT_NAME;
	$skin_img = imagecreatefrompng('https://s3.amazonaws.com/MinecraftSkins/char.png');
	imagealphablending($skin_img, false);
	imagesavealpha($skin_img, true);
	imagepng($skin_img, './'.self::SKINS_FOLDER.'/'.$username.'.png');
	return true;
    }
    
    // Show rendered skin
    public function show_rendered_skin($username, $size = self::DEFAULT_SKINS_SIZE, $type = 'F') {
        $username = preg_replace("#\?.*#", NULL, $username); // for mybb
	$username = str_replace('.png', NULL, $username);
	if ($this->img_exists($username, 'skin') == false) {
	    if ($this->get_skin($username) == false) {
		$skin_img = './'.self::SKINS_FOLDER.'/'.self::DEFAULT_NAME.'.png';
		return $this->render_skin($skin_img, $size, $type);
	    } else {
		$skin_img = './'.self::SKINS_FOLDER.'/'.$username.'.png';
		return $this->render_skin($skin_img, $size, $type);
	    }
	} else {
	    $skin_img = './'.self::SKINS_FOLDER.'/'.$username.'.png';
	    $ts_file = filemtime($skin_img);
	    if ( (time() - $ts_file) > self::CACHE_TIME) {
		$this->get_skin($username);
		return $this->render_skin($skin_img, $size, $type);
	    } else {
		return $this->render_skin($skin_img, $size, $type);
	    }
	}
    }
    
    public function render_skin($skin_img, $skin_height = 256, $type = 'F') {
        $skin_height = intval($skin_height);
        if ($skin_height == 0 OR $skin_height < 0) { $skin_height = self::DEFAULT_SKINS_SIZE; }
	$image = imagecreatefrompng($skin_img);
	$scale = $skin_height / 32;
	$body_canvas = imagecreatetruecolor(16*$scale, 32*$scale);
	imagealphablending($body_canvas, false);
	imagesavealpha($body_canvas, true);
	$transparent = imagecolorallocatealpha($body_canvas, 255, 255, 255, 127);
	imagefilledrectangle($body_canvas, 0, 0, 16*$scale, 32*$scale, $transparent);
        if ($type == 'F') {
            // Head
            $avatar = $this->render_avatar($skin_img, 8, 0);
            imagecopyresized($body_canvas, $avatar, 4*$scale, 0*$scale, 0, 0, 8*$scale, 8*$scale, 8, 8);
            // Body Front
            imagecopyresized($body_canvas, $image, 4*$scale, 8*$scale, 20, 20, 8*$scale, 12*$scale, 8, 12);
            // Right Arm (left on img)
            $r_arm = imagecreatetruecolor(4, 12);
            imagecopy($r_arm, $image, 0, 0, 44, 20, 4, 12);
            imagecopyresized($body_canvas, $r_arm, 0*$scale, 8*$scale, 0, 0, 4*$scale, 12*$scale, 4, 12);
            // Right leg (left on img)
            $r_leg = imagecreatetruecolor(4, 20);
            imagecopy($r_leg, $image, 0, 0, 4, 20, 4, 12);
            imagecopyresized($body_canvas, $r_leg, 4*$scale, 20*$scale, 0, 0, 4*$scale, 12*$scale, 4, 12);
        } elseif ($type == 'B') {
            // Head
            $avatar = $this->render_avatar($skin_img, 8, 0, 'B');
            imagecopyresized($body_canvas, $avatar, 4*$scale, 0*$scale, 0, 0, 8*$scale, 8*$scale, 8, 8);
            // Body Back
            imagecopyresized($body_canvas, $image, 4*$scale, 8*$scale, 32, 20, 8*$scale, 12*$scale, 8, 12);
            // Right Arm Back (left on img)
            $r_arm = imagecreatetruecolor(4, 12);
            imagecopy($r_arm, $image, 0, 0, 52, 20, 4, 12);
            imagecopyresized($body_canvas, $r_arm, 0*$scale, 8*$scale, 0, 0, 4*$scale, 12*$scale, 4, 12);
            // Right leg Back (left on img)
            $r_leg = imagecreatetruecolor(4, 20);
            imagecopy($r_leg, $image, 0, 0, 12, 20, 4, 12);
            imagecopyresized($body_canvas, $r_leg, 4*$scale, 20*$scale, 0, 0, 4*$scale, 12*$scale, 4, 12);
        }
        // Left Arm (right flipped)
        $l_arm = imagecreatetruecolor(4, 12);
        for ($x = 0; $x < 4; $x++) {
            imagecopy($l_arm, $r_arm, $x, 0, 4 - $x - 1, 0, 1, 12);
        }
        imagecopyresized($body_canvas, $l_arm, 12*$scale,  8*$scale,  0,  0,  4*$scale,  12*$scale, 4,  12);
        // Left leg (right flipped)
        $l_leg = imagecreatetruecolor(4, 20);
        for ($x = 0; $x < 4; $x++) {
            imagecopy($l_leg, $r_leg, $x, 0, 4 - $x - 1, 0, 1, 20);
        }
        imagecopyresized($body_canvas, $l_leg, 8*$scale, 20*$scale,  0,  0,  4*$scale,  12*$scale, 4,  12);
        header('Cache-Control: public, max-age='.self::CACHE_TIME);
        header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + self::CACHE_TIME));
	header('Content-Type: image/png');
	return imagepng($body_canvas, NULL, 7, PNG_NO_FILTER);
    }
    
    // Create avatar from skin
    public function avatar($username, $size = self::DEFAULT_HEADS_SIZE) {
	$username = preg_replace("#\?.*#", NULL, $username); // for mybb
	$username = str_replace('.png', NULL, $username);
	if ($this->img_exists($username) == false) {
	    if ($this->get_skin($username) == false) {
		$skin_img = './'.self::SKINS_FOLDER.'/'.self::DEFAULT_NAME.'.png';
		return $this->render_avatar($skin_img, $size);
	    } else {
		$skin_img = './'.self::SKINS_FOLDER.'/'.$username.'.png';
		return $this->render_avatar($skin_img, $size);
	    }
	} else {
	    $skin_img = './'.self::SKINS_FOLDER.'/'.$username.'.png';
	    $ts_file = filemtime($skin_img);
	    if ( (time() - $ts_file) > self::CACHE_TIME) {
		$this->get_skin($username);
		return $this->render_avatar($skin_img, $size);
	    } else {
		return $this->render_avatar($skin_img, $size);
	    }
	}
    }
    
    // Render avatar (only head from skin image)
    public function render_avatar($skin_img, $size = self::DEFAULT_HEADS_SIZE, $header = 1, $type = 'F') {
	if ($size == NULL OR $size <= 0) { $size = self::DEFAULT_HEADS_SIZE; }
	// generate png from url/path
	@$image = imagecreatefrompng($skin_img);
	@imagealphablending($image, false);
	@imagesavealpha($image, true);
        // Head
	$avatar = imagecreatetruecolor($size, $size);
        // Helm
        $helm_check = imagecreatetruecolor($size, $size);
	imagealphablending($helm_check, false);
	imagesavealpha($helm_check, true);
        $transparent = imagecolorallocatealpha($helm_check, 255, 255, 255, 127);
        imagefilledrectangle($helm_check, 0, 0, 8, 8, $transparent);
        switch ($type) {
            case 'F':
                // Avatar front
                @imagecopyresampled($avatar, $image, 0, 0, 8, 8, $size, $size, 8, 8);
                @imagecopyresampled($helm_check, $image, 0, 0, 40, 8, 8, 8, 8, 8);
                break;
            case 'B':
                // Avatar back
                @imagecopyresampled($avatar, $image, 0, 0, 24, 8, $size, $size, 8, 8);
                @imagecopyresampled($helm_check, $image, 0, 0, 56, 8, 8, 8, 8, 8);
                break;
        }
	// Check for helm image
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
	if ( ( ($stddev_red > self::DEFAULT_STDDEV AND $stddev_green > self::DEFAULT_STDDEV) OR 
		($stddev_red > self::DEFAULT_STDDEV AND $stddev_blue > self::DEFAULT_STDDEV) OR 
		($stddev_green > self::DEFAULT_STDDEV AND $stddev_blue > self::DEFAULT_STDDEV) ) OR 
		($mean_alpha == 127) ) {
	    $helm = imagecreatetruecolor($size, $size);
	    imagealphablending($helm, false);
	    imagesavealpha($helm, true);
	    $transparent = imagecolorallocatealpha($helm, 255, 255, 255, 127);
	    imagefilledrectangle($helm, 0, 0, $size, $size, $transparent);
	    imagecopyresampled($helm, $image, 0, 0, 40, 8, $size, $size, 8, 8);
	    $merge = imagecreatetruecolor($size, $size); 
	    imagecopy($merge, $avatar, 0, 0, 0, 0, $size, $size); 
	    imagecopy($merge, $helm, 0, 0, 0, 0, $size, $size); 
	    imagecopymerge($avatar, $merge, 0, 0, 0, 0, $size, $size, 0);
	    if ($header == 1 ) {
                header('Cache-Control: public, max-age='.self::CACHE_TIME);
                header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + self::CACHE_TIME));
		header('Content-Type: image/png'); 
		return imagepng($merge, NULL, 7, PNG_NO_FILTER);
	    } else {
		return $merge;
	    }
	     // return avatar with helm
	} else {
	    if ($header == 1 ) {
                header('Cache-Control: public, max-age='.self::CACHE_TIME);
                header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + self::CACHE_TIME));
		header('Content-Type: image/png'); 
		return imagepng($avatar, NULL, 7, PNG_NO_FILTER); // return avatar without helm
	    } else {
		return $avatar;
	    }
	}
    }
    public function download_skin($username) {
	if (!$this->img_exists($username)) {
	    $username = self::DEFAULT_NAME;
	}
	$image = imagecreatefrompng('./'.self::SKINS_FOLDER.'/'.$username.'.png');
	imagealphablending($image, true);
	imagesavealpha($image, true);
	header('Content-Disposition: Attachment;filename='.$username.'.png'); 	 
	header('Content-type: image/png');
	return imagepng($image);
    }
    
    // Get a random avatar from saved skins
    public function random_avatar($size = self::DEFAULT_HEADS_SIZE) {
	$all_skin = scandir(self::SKINS_FOLDER);
	$rand = rand(2, count($all_skin));
	$username = str_replace(".png", NULL, $all_skin[$rand]);
	header('Content-Disposition: inline; filename="'.$username.'.png";');
	return $this->avatar($username, $size);
    }
    
    public function update($username) {
	if ($this->get_skin($username) == TRUE) {
	    return $this->avatar($this->username);
	} else {
	    return $this->avatar(self::DEFAULT_NAME);
	}
    }
    
    // Check if img exist
    private function img_exists($username) {
        if(file_exists('./'.self::SKINS_FOLDER.'/'.$username.'.png')) {
            return true;
        } else {
            return false;
        }
    }
}