<?php 
ini_set('display_errors',0);
require_once('includes/common.php');
require_once('includes/settings.php');

if(strlen($paintcolor) == 40) // hash
{
	$requested_image = str_replace('.png','.'.$paintcolor.'.png',$requested_image);
	
	$paintcolor = 0;
}

//$bits = explode('/',$_REQUEST['page']);
//array_shift($bits);

//$dir = array_shift($bits);

//$args = array_pop($bits);

//$requested_image = implode('/',$bits);

// args
$as = explode('.',$query);

$images_ignore = array('teampaint.png');

foreach($as as $arg)
{
	$max = str2int($arg);
	if(stripos($arg,'xy') !== false)
		$width = $height = $max;
	if(stripos($arg,'y') !== false)
		$height = $max;
	if(stripos($arg,'x') !== false)
		$width = $max;
}
//die($requested_image." -- ".$color);


if($width == $height)
	$args = 'xy'.$width;
else
	$args = sprintf("x%s.y%s",$width, $height);

if($paintcolor > 0)
	$new_image = str_replace('.png','.'.$paintcolor.'.png',$requested_image);
else
	$new_image = $requested_image;

// cache check
$target_file = $settings['upload']['resized'][$dir].$args.'/'.$new_image;
$ext_file = $settings['upload']['resized_ext'][$dir].$args.'/'.$new_image;


header('Expires: '.gmdate('D, d M Y H:i:s', time()+60*60*24*7*4).'GMT');
	

if(file_exists($target_file))
	serve($target_file);
//	redirect($ext_file);



// get source image
$uploaded_file = $settings['upload']['folder'][$dir].$requested_image;
if(!file_exists($uploaded_file))
{
	header( $_SERVER[ 'SERVER_PROTOCOL' ] . ' 400 Bad Request' );
	error($uploaded_file. " missing");
}
	
$i = new Imagick($uploaded_file);

if($paintcolor && !in_array($requested_image,$images_ignore))
{
	if($paintcolor == 1)
		$paintcolor = 12073019;
	if($requested_image == "paintcan.png")
		$p = new Imagick($settings['upload']['folder'][$dir].'paintcan_paintcolor.png');
	else
		$p = new Imagick($settings['upload']['folder'][$dir].'paint_splatter.png');
	//$p = new Imagick($settings['upload']['folder'][$dir].'paintcan_paintcolor.png');
	//$p->colorizeImage ('#'.dechex($paintcolor),.5);
	//$c = $p->clone();//new Imagick();
	$m = $p->clone();
	$c = new Imagick();
	$c->newImage(500,500,'#'.dechex($paintcolor));
	//$c->setImageAlphaChannel(imagick::ALPHACHANNEL_ACTIVATE);
	$c->compositeImage($m,imagick::COMPOSITE_COPYOPACITY,0,0);
	$p->compositeImage($c,imagick::COMPOSITE_MULTIPLY,0,0);
	$i->compositeImage($p,imagick::COMPOSITE_DEFAULT,0,0);
	//$i = $p;
	//die("!!".$target_file);
}

$geo = $i->getImageGeometry();

// crop the image
if(($geo['width']/$width) < ($geo['height']/$height))
{
    $i->cropImage($geo['width'], floor($height*$geo['width']/$width), 0, (($geo['height']-($height*$geo['width']/$width))/2));
}
else
{
    $i->cropImage(ceil($width*$geo['height']/$height), $geo['height'], (($geo['width']-($width*$geo['height']/$height))/2), 0);
}
// thumbnail the image
$i->ResizeImage($width,$height,imagick::FILTER_LANCZOS,1,true);

imagewritefile($i,$target_file);

/*
$im = imagecreatefromfile($uploaded_file);
if(!$im)
	error("unknown extension for ".$uploaded_file);

$imagex = imagesx($im);
$imagey = imagesy($im);

$aspectx = $imagex/$imagey;
$aspecty = $imagey/$imagex;

if(!$max_x)
	$max_x = $max_y * $aspectx;

if(!$max_y)
	$max_y = $max_x * $aspecty;
	
$x = $max_x;
$y = $max_y;


$sx = $imagex / $x;
$sy = $imagey / $y;
$s  = $sx < $sy ? $sx : $sy;
 
// crop the center of the image 
$x0 = floor( ( $imagex - ( $x * $s ) ) * 0.5 );
$y0 = floor( ( $imagey - ( $y * $s ) ) * 0.5 );
 

$thumb = imagecreatetruecolor($x, $y);
if($dir == 'items')
{
	imagesavealpha($im, true);
	imagesavealpha($thumb, true);
	$color = imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
	imagefill($thumb, 0, 0, $color);
	
}
imagecopyresampled($thumb, $im, 0, 0, $x0, $y0, $x, $y, ($x * $s), ($y * $s));

// handle paint cans
if($paintcolor)
{
	$paint = imagecreatefromfile($settings['upload']['folder'][$dir].'paintcan_paintcolor.png');
	$color = int2rgb($paintcolor);
	
	imagefilter($paint, IMG_FILTER_COLORIZE, $color[0], $color[1], $color[2]);
	imagecopyresampled($thumb, $paint, 0, 0, $x0, $y0, $x, $y, ($x * $s), ($y * $s));
	
	$target_file = str_replace('.p'.$paintcolor,'/'.$paintcolor.'p_',$target_file);
	die($target_file);
}



imagewritefile($thumb,$target_file);
*/
serve($target_file);
//redirect($ext_file);

function imagewritefile($i, $file)
{
	$ext = strtolower($file);
	$ext = explode('.',$ext);
	$ext = end($ext);
	
	$i->setImageFormat($ext);
	$i->setCompressionQuality(90);
	$i->writeImage( $file );
	return;
	switch($ext)
	{
		case 'jpg':
		case 'jpeg':
			imagejpeg($im, $file);
			break;
		case 'png':
			imagepng($im, $file);
			break;
		case 'gif':
			imagegif($im, $file);
			break;
		default:
			return false;
	}
}
function int2rgb($color)
{
	$color = dechex($color);
    if ($color[0] == '#')
        $color = substr($color, 1);

    if (strlen($color) == 6)
        list($r, $g, $b) = array($color[0].$color[1],
                                 $color[2].$color[3],
                                 $color[4].$color[5]);
    elseif (strlen($color) == 3)
        list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
    else
        return false;

    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

    return array($r, $g, $b);
}

function imagecreatefromfile($uploaded_file)
{
	$ext = end(explode(".",strtolower($uploaded_file)));
	switch($ext)
	{
		case 'jpg':
		case 'jpeg':
			return imagecreatefromjpeg($uploaded_file);
		case 'png':
			return imagecreatefrompng($uploaded_file);
		case 'gif':
			return imagecreatefromgif($uploaded_file);
		default:
			return false;
	}
}
function error($msg)
{
	die ($msg);
}
function serve($file)
{
	$ext = strtolower($file);
	$ext = explode('.',$ext);
	$ext = end($ext);
	switch($ext)
	{
		case 'jpg':
		case 'jpeg':
			header('Content-Type: image/jpeg');
			break;
		case 'png':
			header('Content-Type: image/png');
			break;
		case 'gif':
			header('Content-Type: image/gif');
			break;
		default:
			return false;
	}
	echo file_get_contents($file);
	die();
}
function redirect($url)
{
	/*?>
	<img src="<?=$url?>">
	<?php */
	header('Location: '.$url);
	die();
}
?>