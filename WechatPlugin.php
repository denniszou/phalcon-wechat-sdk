<?php
/**
 * 微信公众平台 PHP-Phalcon SDK
 *
 * @author Dennis Zou <denniszou@gmail.com>
 */

class WechatPlugin extends Phalcon\MVC\User\Plugin  {
    private $requestData;   // 解密并解码的服务器请求的POST数据，以数组保存
    private $debugLog;      // Phalcon logger实例，如果为 NULL 则表示不写入log

    /**
     * 构造函数，初始化log对象，验证数字签名，将POST数据解密、解码
     *
     * @param string $token 签名秘钥
     * @param boolean $logFilePath log文件路径，如果为NULL，则不写入log
     */
    public function __construct($token, $logFilePath = NULL ) {
        // 根据传入的路径初始化log对象
        if (!is_null($logFilePath)) {
            $this->debugLog = new Phalcon\Logger\Adapter\File($logFilePath);
        } else {
            $this->debugLog = NULL;
        }

        // 验证数字签名
        if (!$this->validateSignature($token)) {
            $this->log("Signature ail!");
            exit('签名验证失败');
        }

        if ($this->isURLValid()) {
            // 网址接入验证
            $this->log('Valid URL requested.');
            exit($_GET['echostr']);
        }

        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            $this->log('No POST datas');
            exit('缺少数据');
        }

        $xml = (array) simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA);

        $this->requestData = array_change_key_case($xml, CASE_LOWER); // 将数组键名转换为小写，提高健壮性，减少因大小写不同而出现的问题
    }

    /**
     * 判断此次请求是否为公众平台设置URL时的验证请求
     *
     * @return boolean
     */
    private function isURLValid() {
        return isset($_GET['echostr']);
    }

    /**
     * 将log写入文件
     *
     * @param string $msg
     */
    private function log($msg) {
        if (!is_null($this->debugLog)) {
            $this->debugLog->log($msg);
        }
    }

    /**
     * 验证此次请求的签名信息
     *
     * @param  string $token 签名秘钥
     * @return boolean
     */
    private function validateSignature($token) {
        if ( ! (isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']))) {
            return FALSE;
        }

        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];

        $signatureArray = array($token, $timestamp, $nonce);
        sort($signatureArray,SORT_STRING);

        return sha1(implode($signatureArray)) == $signature;
    }

    /**
     * 获取本次请求中的参数，不区分大小
     *
     * @param  string $param 参数名，默认为无参
     * @return mixed
     */
    protected function getRequestData($param = FALSE) {
        if ($param === FALSE) {
            // $param 为 FALSE时返回完整的请求数据
            return $this->requestData;
        }

        $param = strtolower($param);

        if (isset($this->requestData[$param])) {
            return $this->requestData[$param];
        }

        return NULL;
    }

    /**
     * 用户关注时触发，用于子类重写
     *
     * @return void
     */
    protected function onSubscribe() {
    }

    /**
     * 用户取消关注时触发，用于子类重写
     *
     * @return void
     */
    protected function onUnsubscribe() {
    }

    /**
     * 收到文本消息时触发，用于子类重写
     *
     * @return void
     */
    protected function onText() {
        $this->responseText("Welcome to shoplist!");
    }

    /**
     * 收到图片消息时触发，用于子类重写
     *
     * @return void
     */
    protected function onImage() {
    }

    /**
     * 收到地理位置消息时触发，用于子类重写
     *
     * @return void
     */
    protected function onLocation() {
    }

    /**
     * 收到链接消息时触发，用于子类重写
     *
     * @return void
     */
    protected function onLink() {
    }

    /**
     * 收到自定义菜单消息时触发，用于子类重写
     *
     * @return void
     */
    protected function onClick() {
    }

    /**
     * 收到地理位置事件消息时触发，用于子类重写
     *
     * @return void
     */
    protected function onEventLocation() {
    }

    /**
     * 收到语音消息时触发，用于子类重写
     *
     * @return void
     */
    protected function onVoice() {
    }

    /**
     * 扫描二维码时触发，用于子类重写
     *
     * @return void
     */
    protected function onScan() {
    }

    /**
     * 收到未知类型消息时触发，用于子类重写
     *
     * @return void
     */
    protected function onUnknown() {
    }

    /**
     * 回复文本消息
     *
     * @param  string  $content  消息内容
     * @param  integer $funcFlag 默认为0，设为1时星标刚才收到的消息
     * @return void
     */
    protected function responseText($content, $funcFlag = 0) {
        exit(new TextResponse($this->getRequestData('fromusername'), $this->getRequestData('tousername'), $content, $funcFlag));
    }

    /**
     * 回复音乐消息
     *
     * @param  string  $title       音乐标题
     * @param  string  $description 音乐描述
     * @param  string  $musicUrl    音乐链接
     * @param  string  $hqMusicUrl  高质量音乐链接，Wi-Fi 环境下优先使用
     * @param  integer $funcFlag    默认为0，设为1时星标刚才收到的消息
     * @return void
     */
    protected function responseMusic($title, $description, $musicUrl, $hqMusicUrl, $funcFlag = 0) {
        exit(new MusicResponse($this->getRequestData('fromusername'), $this->getRequestData('tousername'), $title, $description, $musicUrl, $hqMusicUrl, $funcFlag));
    }

    /**
     * 回复图文消息
     * @param  array   $items    由单条图文消息类型 NewsResponseItem() 组成的数组
     * @param  integer $funcFlag 默认为0，设为1时星标刚才收到的消息
     * @return void
     */
    protected function responseNews($items, $funcFlag = 0) {
        exit(new NewsResponse($this->getRequestData('fromusername'), $this->getRequestData('tousername'), $items, $funcFlag));
    }

    /**
     * 分析消息类型，并分发给对应的函数
     *
     * @return void
     */
    public function run() {
        switch ($this->getRequestData('msgtype')) {
        case 'event':
            switch ($this->getRequestData('event')) {
            case 'subscribe':
                $this->onSubscribe();
                break;

            case 'unsubscribe':
                $this->onUnsubscribe();
                break;

            case 'SCAN':
                $this->onScan();
                break;

            case 'LOCATION':
                $this->onEventLocation();
                break;

            case 'CLICK':
                $this->onClick();
                break;
            }
            break;

        case 'text':
            $this->onText();
            break;

        case 'image':
            $this->onImage();
            break;

        case 'location':
            $this->onLocation();
            break;

        case 'link':
            $this->onLink();
            break;

        case 'voice':
            $this->onVoice();
            break;

        default:
            $this->onUnknown();
            break;
        }
    }
}

/**
 * 用于回复的基本消息类型
 */
abstract class WechatResponse {
    protected $toUserName;
    protected $fromUserName;
    protected $funcFlag;
    protected $template;

    public function __construct($toUserName, $fromUserName, $funcFlag) {
      $this->toUserName = $toUserName;
      $this->fromUserName = $fromUserName;
      $this->funcFlag = $funcFlag;
    }

    abstract public function __toString();
}

/**
 * 用于回复的文本消息类型
 */
class TextResponse extends WechatResponse {
    protected $content;

    public function __construct($toUserName, $fromUserName, $content, $funcFlag = 0) {
       parent::__construct($toUserName, $fromUserName, $funcFlag);

        $this->content = $content;
        $this->template = <<<XML
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[text]]></MsgType>
  <Content><![CDATA[%s]]></Content>
  <FuncFlag>%s</FuncFlag>
</xml>
XML;
    }

    public function __toString() {
      return sprintf($this->template, $this->toUserName, $this->fromUserName, time(), $this->content, $this->funcFlag);
    }
}

/**
 * 用于回复的音乐消息类型
 */
class MusicResponse extends WechatResponse {
    protected $title;
    protected $description;
    protected $musicUrl;
    protected $hqMusicUrl;

    public function __construct($toUserName, $fromUserName, $title, $description, $musicUrl, $hqMusicUrl, $funcFlag) {
        parent::__construct($toUserName, $fromUserName, $funcFlag);

        $this->title = $title;
        $this->description = $description;
        $this->musicUrl = $musicUrl;
        $this->hqMusicUrl = $hqMusicUrl;
        $this->template = <<<XML
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[music]]></MsgType>
  <Music>
    <Title><![CDATA[%s]]></Title>
    <Description><![CDATA[%s]]></Description>
    <MusicUrl><![CDATA[%s]]></MusicUrl>
    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
  </Music>
  <FuncFlag>%s</FuncFlag>
</xml>
XML;
    }

    public function __toString() {
        return sprintf($this->template, $this->toUserName, $this->fromUserName, time(), $this->title, $this->description, $this->musicUrl, $this->hqMusicUrl, $this->funcFlag);
    }
}

/**
 * 用于回复的图文消息类型
 */
class NewsResponse extends WechatResponse {
    protected $items = array();

    public function __construct($toUserName, $fromUserName, $items, $funcFlag) {
        parent::__construct($toUserName, $fromUserName, $funcFlag);

        $this->items = $items;
        $this->template = <<<XML
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[news]]></MsgType>
  <ArticleCount>%s</ArticleCount>
  <Articles>
    %s
  </Articles>
  <FuncFlag>%s</FuncFlag>
</xml>
XML;
    }

    public function __toString() {
        return sprintf($this->template, $this->toUserName, $this->fromUserName, time(), count($this->items), implode($this->items), $this->funcFlag);
    }
}

/**
 * 单条图文消息类型
 */
class NewsResponseItem {
    protected $title;
    protected $description;
    protected $picUrl;
    protected $url;
    protected $template;

    public function __construct($title, $description, $picUrl, $url) {
        $this->title = $title;
        $this->description = $description;
        $this->picUrl = $picUrl;
        $this->url = $url;
        $this->template = <<<XML
<item>
  <Title><![CDATA[%s]]></Title>
  <Description><![CDATA[%s]]></Description>
  <PicUrl><![CDATA[%s]]></PicUrl>
  <Url><![CDATA[%s]]></Url>
</item>
XML;
    }

    public function __toString() {
        return sprintf($this->template, $this->title, $this->description, $this->picUrl, $this->url);
    }
}


