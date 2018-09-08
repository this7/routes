<?php
/**
 * this7 PHP Framework
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2016-2018 Yan TianZeng<qinuoyun@qq.com>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://www.ub-7.com
 */
namespace this7\routes;
use \Exception;
use \ReflectionMethod;

/**
 * 缓存
 */
class routes {
    /**
     *核心应用
     */
    public $app = [];
    /**
     * 路由地址
     * @var string
     */
    public $url = [];

    /**
     * 路由地址
     * @var string
     */
    public $par = [];

    public function __construct($app = '') {
        $this->app = $app;
    }

    /**
     * 启动路由
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function start($url = '', $par = '') {
        $url = $this->getRoutePath();
        #检查微信URL
        $url = $this->wechatURl($url);
        #判断是否为空 则设置默认值
        if (empty($url[0])) {
            $type   = 'page';
            $app    = 'client';
            $model  = 'home';
            $action = 'home';
        }
        #获取地址字段
        else {
            #偶数页面
            if (count($url) % 2 == 0) {
                $type  = 'page';
                $app   = 'client';
                $model = !empty($url[0]) ? $url[0] : 'home';
                array_shift($url);
                $action = !empty($url[0]) ? $url[0] : 'home';
                array_shift($url);
            }
            #API访问
            elseif (strtolower($url[0]) === 'api' || strtolower($url[0]) === 'dapi' || strtolower($url[0]) === 'system') {
                $type = strtolower($url[0]);
                $app  = $url[0];
                array_shift($url);
                $model = !empty($url[0]) ? $url[0] : 'demo';
                array_shift($url);
                $action = !empty($url[0]) ? $url[0] : 'index';
                array_shift($url);
            }
            #奇数访问
            else {
                $type = 'page';
                $path = !empty($url[0]) ? $url[0] : 'home';
                $app  = 'client/' . $path;
                array_shift($url);
                $model = !empty($url[0]) ? $url[0] : 'home';
                array_shift($url);
                $action = !empty($url[0]) ? $url[0] : 'home';
                array_shift($url);
            }
        }
        #将参数以数组保存到URL
        $this->url = compact('type', 'app', 'model', 'action');
        #获取参数字段
        for ($i = 0; $i < count($url); $i += 2) {
            $value               = isset($url[$i + 1]) ? $url[$i + 1] : '';
            $this->par[$url[$i]] = $value;
        }
        $_GET = empty($this->par) ? $this->url : array_merge($this->url, $this->par);
        #执行DEBUG
        debug::bootstrap();
        if ($type == 'system') {
            $this->actionSystem($model, $action);
            exit("系统内置API");
        }
        #执行中间件
        middleware::run();
        #执行页面或API
        if ($type == 'api' || $type == 'dapi') {
            $this->startApi($model, $action);
        } else {
            view::display();
        }
    }

    /**
     * 微信路由地址
     * @Author   Sean       Yan
     * @DateTime 2018-08-09
     * @param    string     $url [description]
     * @return   [type]            [description]
     */
    public function wechatURl($url = '') {
        $weixin = array('system', 'wechat');
        $string = trim($_SERVER['REQUEST_URI'], "/");
        #判断是否是微信链接-内部跳转链接
        if (strstr($string, md5('wechat') . "_wechat")) {
            $weixin[] = 'userAuthorization';
            #获取自定义数据 4910_wechat/type/get/callback/-1?code=
            #为了获取URL地址中间4910_wechat到？之间的数据为自定义参数
            $str    = strlen($wechat);
            $len    = strrpos($string, '?');
            $num    = $len - $str;
            $new    = trim(substr($string, $str, $num), '/');
            $weixin = array_merge($weixin, explode("/", $new));
            #格式化数据
            $pieces = explode("?", $string);
            parse_str($pieces[1], $array);
            foreach ($array as $key => $value) {
                $weixin[] = $key;
                $weixin[] = $value;
            }
            return $weixin;
        }
        #第三方-授权事件接收URL  md5("Wechat callback") . "_wechat"; //事件回调
        elseif (strstr($string, md5('Wechat Third-party callback') . "_wechat")) {
            #thirdPartiesMessages
            $weixin[] = 'thirdPartiesMessages';
            $pieces   = explode("?", $string);
            if (isset($pieces[1]) && !empty($pieces[1])) {
                parse_str($pieces[1], $array);
                foreach ($array as $key => $value) {
                    $weixin[] = $key;
                    $weixin[] = $value;
                }
            }

        }
        #第三方-授权事件回调地址
        elseif (strstr($string, md5('Wechat authorization callback') . "_wechat")) {
            # code...
            echo '回调授权';
            exit();
        }
        #第三方-消息与事件接收URL
        elseif (strstr($string, md5('Wechat callback') . "_wechat")) {
            # code...
        } else {
            return $url;
        }

    }

    /**
     * 执行系统函数
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @param    string     $class  [description]
     * @param    string     $action [description]
     * @return   [type]             [description]
     */
    public function actionSystem($class = '', $action = '') {
        switch ($class . '-' . $action) {
        case 'view-saveES5':
            view::saveES5();
            break;
        case 'view-showES5':
            view::showES5();
            break;
        case 'view-getES5':
            view::getES5();
            break;
        case 'view-onekey':
            view::onekey();
            break;
        case 'wechat-userAuthorization':
            wechat::userAuthorization();
            break;
        case 'wechat-thirdPartiesMessages':
            wechat::thirdPartiesMessages($_GET);
            break;
        case 'wechat-login':
            wechat::login();
            break;
        case 'wechat-demo':
            wechat::demo();
            break;
        default:
            # code...
            break;
        }

    }

    /**
     * 获取路由地址
     * @param  string $url [description]
     * @return [type]       [description]
     */
    public function getRoutePath($url = "") {
        if (isset($_SERVER['REDIRECT_QUERY_STRING']) && empty($url)) {
            $url = $_SERVER['REDIRECT_QUERY_STRING'];
        }
        if (isset($_SERVER['REQUEST_URI']) && empty($url)) {
            $url = $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['PATH_INFO']) && empty($url)) {
            $url = $_SERVER['PATH_INFO'];
        }
        return explode('/', trim($url, ' / '));
    }

    /**
     * 启动API调用
     * @param  string $class  类文件名
     * @param  string $action 执行动作
     * @return [type]         [description]
     */
    public function startApi($class = '', $action = '') {
        $new_class = 'server\controllers\\' . $class . md5($class);
        #判断类文件是否存在
        $this->setApiClass($class, $action);
        #判断控制器是否存在
        if (!class_exists($new_class)) {
            echo $class . "API控制器不存在，是否创建";
        }
        #载入到系统核心类
        $Plugin = $this->app->make($new_class, false);
        #执行动作
        try {
            $reflection = new ReflectionMethod($Plugin, $action);
            if ($reflection->isPublic()) {
                #执行动作
                if ($result = call_user_func_array([$Plugin, $action], [])) {
                    #执行DEBUG显示
                    debug::display(["model" => "api"]);
                    die();
                } else {
                    #执行DEBUG显示
                    debug::display(["model" => "api"]);
                }
            } else {
                throw new Exception("访问[$class]API控制器中[$action]方法不是public方法", 10003);
            }
        } catch (ReflectionException $e) {
            $action = new ReflectionMethod($Plugin, '__call');
            $action->invokeArgs($Plugin, [$action, '']);
        }
    }

    /**
     * 设置API文件
     * @Author   Sean       Yan
     * @DateTime 2018-07-30
     * @param    string     $class  类名
     * @param    string     $action 方法名
     * @param    string     $type   调用类型
     */
    public function setApiClass($class = '', $action = '', $type = 1) {
        $file = ROOT_DIR . "/api/" . $class . ".php";
        $new  = ROOT_DIR . "/server/controllers/" . $class . md5($class) . ".php";
        if (!is_file($file) && is_file($new)) {
            return;
        }
        #如果文件不存在 提示创建
        if (!is_file($file)) {
            if ($type) {
                $name = md5($class);
                if (isset($_GET['key']) && $_GET['key'] == $name) {
                    $php = <<<PHP
<?php
class $class {
    public function index() {
        echo "欢迎访问API";
    }
}
PHP;
                    to_mkdir($file, $php, true, true);
                    redirect("api/" . $class . '/index');
                }
                $url = site_url($_GET['app'] . "/" . $_GET['model'] . "/" . $_GET['action'], "key/" . $name);
                echo "您访问的Api控制器类不存在，<a href='" . $url . "'>点击此处立即创建</a>";
                exit();
            } else {
                return false;
                exit();
            }
        }
        #执行编译
        $status = DEBUG || !file_exists($new) || !is_file($new) || (filemtime($file) > filemtime($new));
        if ($status) {
            #修改命名空间
            $content   = file_get_contents($file);
            $namespace = '<?php
namespace server\controllers;
use \Exception;';
            $content = preg_replace('/^\<\?php/is', $namespace, $content);
            #解析类名称
            $content = preg_replace('/class\s?(' . $class . ')/is', 'class ' . $class . md5($class), $content);
            #解析变量数集
            $content = preg_replace("/R\s*\(\s*function\s*\(([\w\$]*)\)\s*\{/is", 'return R(function (\1){extract(\1);', $content);
            $content = preg_replace("/callFunc\s*\(\s*function\s*\(([\w\$]*)\)\s*\{/is", 'callFunc(function (\1){extract(\1);', $content);
            to_mkdir($new, $content, true, true);
            #压缩PHP代码
            $content = php_strip_whitespace($new);
            to_mkdir($new, $content, true, true);
        }
    }

    /**
     * URL地址获取
     * @param  sting $address   需要解析的地址用/分割
     * @param  sting $parameter 需要解析的参数
     * @return url              返回路径
     */
    public function getUrl($address = NULL, $parameter = NULL) {
        if (strstr($address, "http://") || strstr($address, "https://") || strstr($address, "//")) {
            return $address;
        }
        $array = explode("/", $address);
        $count = count($array);
        $par   = array();
        $url   = null;
        switch ($count) {
        case '3':
            $root     = rtrim(ROOT, "/") . '/' . $array[0];
            $par['c'] = $array[1];
            $par['a'] = $array[2];
            break;
        case '2':
            $root     = rtrim(ROOT, "/");
            $par['c'] = $array[0];
            $par['a'] = $array[1];
            break;
        default:
        case '1':
            $root     = rtrim(ROOT, "/");
            $par['c'] = $_GET['model'];
            $par['a'] = $array[0];
            break;
        }
        #转换参数信息
        if (!empty($parameter)) {
            if (strstr($parameter, "=")) {
                $array = explode(';', $parameter);
                foreach ($array as $key => $value) {
                    $value          = explode('=', $value);
                    $par[$value[0]] = $value[1];
                }
            } elseif (strstr($parameter, "/")) {
                $array = explode('/', $parameter);
                for ($i = 0; $i < count($array); $i += 2) {
                    $par[$array[$i]] = $array[$i + 1];
                }
            } elseif (is_array($parameter)) {
                $par = $parameter;
            }
        }
        #进行参数拼接
        foreach ($par as $key => $value) {
            if ($key == 'c' || $key == 'a' || $key == 'w') {
                $url .= "/{$value}";
            } else {
                $url .= "/{$key}/{$value}";
            }
        }
        return $root . $url;
    }
}