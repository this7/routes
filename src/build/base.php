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
namespace this7\routes\build;

class base {
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
        #检查视图URL
        if (class_exists('view')) {
            $url = view::viewURL($url);
        }
        #检查图片URL
        if (class_exists('images')) {
            $url = images::imagesURL($url);
        }
        #检查微信URL
        if (class_exists('wechat')) {
            $url = wechat::wechatURL($url);
        }
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
        #执行系统API
        if ($type == 'system') {
            call_user_func([$model, $action], $_GET);
        }
        #执行页面或API
        else {
            #执行中间件
            middleware::run();
            #执行页面或API
            if ($type == 'api' || $type == 'dapi') {
                api::startApi($model, $action, $type, $this->app);
            } else {
                view::display();
            }
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
        return explode('/', trim($url, '/'));
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
