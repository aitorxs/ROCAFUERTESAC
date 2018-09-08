<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */

$res=@include("../../../master.inc.php");
if (! $res) $res=@include("../../../../master.inc.php");               // For "custom" directory

$id= GETPOST('id');
$w= GETPOST('w');
$h= GETPOST('h');
$query= GETPOST('query');

// Content type
header('Content-Type: image/jpeg');

if ($query=="cat")
{

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

$object = new Categorie($db);
$result = $object->fetch($id);
$upload_dir = $conf->categorie->multidir_output[$object->entity];
$pdir = get_exdir($object->id,2,0,0,$object,'category') . $object->id ."/photos/";
$dir = $upload_dir.'/'.$pdir;
foreach ($object->liste_photos($dir) as $key => $obj)
	{
	$filename=$obj['photo'];
	}

// The file
$filename = $dir.$filename;
if (!file_exists($filename)) $filename="empty.jpg";

// Dimensions
list($width, $height) = getimagesize($filename);
$new_width = $w;
$new_height = $h;

// Resample
$image_p = imagecreatetruecolor($new_width, $new_height);
$image = imagecreatefromjpeg($filename);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

// Add icon
$icon = imagecreatefromjpeg('add.jpg');
list($width, $height) = getimagesize('add.jpg');
$new_width = $w*0.3;
$new_height = $h*0.3;
$icon_p = imagecreatetruecolor($new_width, $new_height);
imagecopyresampled($icon_p, $icon, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
imagecopymerge($image_p, $icon_p,  0, $h*0.7, 0, 0, $new_width, $new_height, 100);


// Output
imagejpeg($image_p, null, 100);
}




else if ($query=="pro")
{
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
$objProd = new Product($db);
$objProd->fetch($id);
$pdir = get_exdir($id,2,0,0,$objProd,'product') . $id ."/photos/";
$dir = $conf->product->multidir_output[$objProd->entity].'/'.$pdir;
foreach ($objProd->liste_photos($dir) as $key => $obj)
	{
	$filename=$obj['photo'];
	}
$filename = $dir.$filename;
if (!file_exists($filename)) $filename="empty.jpg";
// Dimensions
list($width, $height) = getimagesize($filename);
$new_width = $w;
$new_height = $h;

// Resample
$image_p = imagecreatetruecolor($new_width, $new_height);
$image = imagecreatefromjpeg($filename);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

// Output
imagejpeg($image_p, null, 100);
}






else
{
// The file
$filename = $query.".jpg";

// Dimensions
list($width, $height) = getimagesize($filename);
$new_width = $w;
$new_height = $h;

// Resample
$image_p = imagecreatetruecolor($new_width, $new_height);
$image = imagecreatefromjpeg($filename);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

// Output
imagejpeg($image_p, null, 100);
}
