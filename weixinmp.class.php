<?php
class WeixinMP {
    protected $ToUserName;
    protected $FromUserName;
    public $ruleDir      = './rules/';
    /*以下三项值规则为：为false不处理该类型，其他值先在$ruleDir查找是否存在与值相同的php文件，若无或文件为空，则将值作为文本返回*/
    public $textRule     = 'text';
    public $welcomeRule  = 'welcome';
    public $locationRule = 'location';
    const ERR_POST_EMPTY   = 4201;
    const ERR_TYPE_ENPTY   = 4202;
    const ERR_RULE_EMPTY   = 4203;
    const ERR_NO_RULE_FILE = 4204;
    const ERR_UNKNOW_TYPE  = 4205;


    public function __construct($token=''){
        /*自动处理验证逻辑*/
        $token = !empty($token) ? $token : (defined('WX_TOKEN') ? WX_TOKEN : '');
        if(empty($token)){
            return false;
        }
        $valid = $this->valid($token);
        if(!$valid){
            exit;
        }
        if($_SERVER['REQUEST_METHOD'] == 'GET'){
            echo $valid;
            exit;
        }
    }

    private function tpl(){
        $argv = func_get_args();
        return call_user_func_array('sprintf', $argv);
    }

    /*文字消息格式*/
    public function send($text='42', $flag=0){
        $tpl = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[%s]]></Content>
                <FuncFlag>%s</FuncFlag>
                </xml>";
        echo $this->tpl($tpl, $this->FromUserName, $this->ToUserName, time(), $text, $flag);
    }

    /*图文消息格式*/
    public function sendImgs($imgs, $content='', $flag=0){
        $tpl = "<xml>
                 <ToUserName><![CDATA[%s]]></ToUserName>
                 <FromUserName><![CDATA[%s]]></FromUserName>
                 <CreateTime>%s</CreateTime>
                 <MsgType><![CDATA[news]]></MsgType>
                 <Content><![CDATA[%s]]></Content>
                 <ArticleCount>%s</ArticleCount>
                 <Articles>%s</Articles>
                 <FuncFlag>%s</FuncFlag>
                 </xml>";
        $tpl_img = "<item>
                    <Title><![CDATA[%s]]></Title>
                    <Discription><![CDATA[%s]]></Discription>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                    </item>";
        $imgsXml = '';
        foreach ($imgs as $img) {
            $imgsXml .= call_user_func_array(array($this, 'tpl'), array_merge(array($tpl_img), array_pad($img, 4, '')));
        }
        echo $this->tpl($tpl, $this->FromUserName, $this->ToUserName, time(), $content, count($imgs), $imgsXml, $flag);
    }

    /*发送消息前初始化*/
    public function initSend($ToUserName, $FromUserName){
        $this->ToUserName = $ToUserName;
        $this->FromUserName = $FromUserName;
    }

    /*获取POST数据*/
    public function getPostData(){
        $postData = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postObj = null;
        if (!empty($postData)){
            $postObj = simplexml_load_string($postData, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        $this->postObj = $postObj;
        return $postObj;
    }

    /*调用消息处理文件*/
    protected function doRules(){
        $argv = func_get_args();
        $filename = $argv[0];
        if($filename === false){
            throw new Exception('response empty', self::ERR_RULE_EMPTY);
        }
        if(isset($argv[1]) && gettype($argv[1]) == 'array'){
            foreach ($argv[1] as $key => $value) {
                $$key = $value;
            }
        }

        $file = $this->ruleDir . $filename . '.php';
        if(file_exists($file) && file_get_contents($file) != ''){
            try {
                include($file);
            } catch (Exception $e) {
                throw $e;
            }
        } else if(!isset($magic)) {
            $this->send($filename);
        } else {
            throw new Exception('no rule file', self::ERR_NO_RULE_FILE);
        }
    }

    /*处理文本消息*/
    public function doText($argv){
        $content = trim($argv['Content']);
        if(!$content){
            throw new Exception("no content", 1);
        }
        $this->doRules($this->textRule, $argv);
    }

    /*发送欢迎消息*/
    public function doWelcome(){
        $this->doRules($this->welcomeRule);
    }

    /*处理地理位置消息*/
    public function doLocation($argv){
        $this->doRules($this->locationRule, $argv);
    }

    /*返回处理*/
    public function response(){
        $postObj = $this->getPostData();
        if(!$postObj){
            throw new Exception("no post data", self::ERR_POST_EMPTY);
        }
        $this->initSend($postObj->ToUserName, $postObj->FromUserName);
        $type = $postObj->MsgType;
        if(!(string)$type){
            throw new Exception("type empty", self::ERR_TYPE_ENPTY);
        } else {
            if($type == 'event' && $postObj->Event == 'subscribe'){
                $type = 'welcome';
            }
            $funcName = 'do' . ucfirst($type);
            if(method_exists($this, $funcName)){
                try {
                    call_user_func(array($this, $funcName), (array)$postObj);
                } catch (Exception $e) {
                    throw $e;
                }
            } else {
                throw new Exception($type, self::ERR_UNKNOW_TYPE);
            }
        }
    }

    /**
     * [其他类型消息处理]
     * 可以直接调用doTest方法，默认会调用rules目录下的test.php脚本来处理
     */
    public function __call($name, $argv){
        if(preg_match('/^do[A-Z]/', $name)){
            $argv['magic'] = true;
            $this->doRules(strtolower(preg_replace('/^do_/', '', preg_replace('/[A-Z]/', '_$0', $name))), $argv);
        }
    }

    /*接口验证*/
    public function valid($token){
        /*获取get参数*/
        $queryFilter = array('signature', 'timestamp', 'nonce', 'echostr');
        foreach ($queryFilter as $key) {
            $$key = isset($_GET[$key]) ? htmlspecialchars(urldecode($_GET[$key])) : '';
        }

        $qarr = array($token, $timestamp, $nonce);
        sort($qarr);
        $sha1Querys = sha1(implode($qarr));

        if($sha1Querys == $signature){
            return $echostr ? $echostr : true;
        }else{
            return false;
        }
    }
}