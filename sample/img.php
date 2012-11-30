<?php
/*微信指定图片需要和接口同域，发现用php返回图片是可用的*/

$img = urldecode($_GET['i']);
preg_match('/\.(\w+)$/', $img, $matchs);
$type = $matchs[1];

if(!(in_array($type, array('jpg', 'png')))){
	$type = 'jpg';
}

Header('Content-Type:image/' . $type);
echo file_get_contents(htmlspecialchars($img));