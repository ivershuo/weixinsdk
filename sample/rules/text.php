<?php
/*BAE的php版本是5.2，郁闷~*/
class func{
	static function f1($wx, &$Content){
		$wx->send('hello');
	}
	static function f42($wx, &$Content){
		$wx->send('42');
	}
}
$rules = array(
	array('/^Hello\W|$/i', 'f1'),
	array('/(终极答案)|(宇宙.*?生命.*?一切)|(life.*?universe.*?everything)/', 'f42')
);

$match = false;
foreach ($rules as $rule) {
	if(preg_match($rule[0], $Content)){
		$match = true;
		call_user_func_array(array('func', $rule[1]), array(&$this, &$Content));
		break;
	}
}

if(!$match){
	$texts = array(
		'生活，厌恶它或者忽略它，总之不可能喜欢它。',
		'请你不必管我。',
		'非常沮丧。',
		'别装出一副想和我说话的样子，我知道你恨我。',
		'是的，你就是恨我，大家都恨我。这是宇宙形态的一部分。我不得不和一些人交谈，然后他们就开始恨我了。甚至机器人也恨我。如果你没有注意到我的话，我想我很可能会走开的。',
		'那艘飞船也恨我',
		'它恨我，因为我对它说话了。',
		'很简单。我非常无聊和沮丧，所以就跑过去把自己和它外面的电脑对接口联了起来。我对那台电脑说了很久很久，向它解释了我的观点，从宇宙一直到它自己。',
		'我能把那张纸捡起来吗？！我，拥有相当于一个星球的智力，他们却叫我……',
		'如果你希望的话，我甚至能跑去把我的脑袋浸在一桶水里。你希望我把脑袋浸在一桶水里吗？我已经准备好了。你等一等。'
	);
	$this->send($texts[rand(0, count($texts)-1)]);
}