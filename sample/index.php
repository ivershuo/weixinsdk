<?php
/*
* Example。
*/
define('WX_TOKEN', 'you token here');

require('BaeMemcache.class.php'); //(codes on BAE)
require('../weixinmp.class.php');

class JokeWx extends WeixinMP{
	static public function getJokes(){
		$jokeMem = new BaeMemcache();
		$jokes = $jokeMem->get('joke');
		return $jokes ? json_decode($jokes) : false;
	}

	public function dotext($argv){
		$jokes = self::getJokes();
		if(!$jokes){
			$this->send('我还没睡醒涅~');
		} else {
			$max = count($jokes) - 1;
			$joke = $jokes[rand(0, $max)];
			$title = $joke[1];
			$discription = str_replace('##', "\n", str_replace('　　', '', $joke[2]));
			if($joke[3]){
				$imgUrl = 'http://example.duapp.com/img.php?i=' . urlencode($joke[3]);				
				$this->sendImgs(array(
					array(
						$title,
						$discription,
						$imgUrl,
						$joke[3]
					)
				));
			} else {
				$this->send('《' . $title . "》\n" . $discription);
			}
		}
	}
}
$wx = new JokeWx();
$wx->welcomeRule = '我现在比较蠢，不管你对我说啥，我都会给你讲一个笑话~';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	try {
		$wx->response();
	} catch (Exception $e) {
		$wx->send('我发生错误了...');
	}	
}

function getJokeFromWeb(){
	$htmlGet = file_get_contents('http://hao.360.cn/youmoxiaohua.html');
	$jokeHtml = preg_replace('/\s+/', '', $htmlGet);
	$m = preg_match('/varjokes\=(.*?);varcurId/', $jokeHtml, $jokeData);
	if($m){
		return $jokeData[1];
	}
}
if(!JokeWx::getJokes()){
	$joke = getJokeFromWeb();
	$jokeMem = new BaeMemcache();
	$jokeMem->set('joke', $joke, 0, 86400);
}

//=======================================
/*或者可以偷懒用如下方式，在相关rules文件中直接写处理逻辑*/
/*
$wx = new WeixinMP();
$wx->welcomeRule = false;

if($_SERVER['REQUEST_METHOD'] == 'POST'){
	try {
		$wx->response();
	} catch (Exception $e) {
		$errCode = $e->getCode();
		switch ($errCode) {
			case WeixinMP::ERR_TYPE_ENPTY :
				$wx->send('我拥有相当于一个星球的智力，你却叫我……');
				break;
			case WeixinMP::ERR_UNKNOW_TYPE :
				if($e->getMessage() == 'image'){
					$wx->doImage();
				}
				break;
			default :
				print_r($e);
		}
	}	
}
*/