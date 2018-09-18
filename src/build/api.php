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
use \Exception;
use \ReflectionMethod;
use \this7\framework\ErrorCode;

/**
 *API调用接口
 */
class api {

    /**
     * 启动API调用
     * @param  string $class  类文件名
     * @param  string $action 执行动作
     * @return [type]         [description]
     */
    public static function startApi($class = '', $action = '', $type = 'api', $app) {
        #编译的类名
        $new_class = 'server\controllers\\' . $class . md5($class);
        #判断类文件是否存在
        self::setApiClass($class, $action);
        #执行动作
        try {
            #载入到系统核心类
            $Plugin = $app->make($new_class, false);
            #检查类文件
            $reflection = new ReflectionMethod($Plugin, $action);
            if ($reflection->isPublic()) {
                #执行动作
                if ($result = call_user_func_array([$Plugin, $action], [])) {
                    switch ($type) {
                    case 'api':
                        throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE), ErrorCode::$OK);
                        break;
                    case 'dapi':
                        \this7\debug\debug::display($result);
                        break;
                    }
                } else {
                    throw new Exception("无法访问", ErrorCode::$ClassDoesNotExist);
                }
            } else {
                throw new Exception("访问[$class]API控制器中[$action]方法不是public方法", ErrorCode::$ClassDoesNotExist);
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
     */
    public static function setApiClass($class = '', $action = '') {
        try {
            $file = ROOT_DIR . "/api/" . $class . ".php";
            $new  = ROOT_DIR . "/server/controllers/" . $class . md5($class) . ".php";
            if (!is_file($file) && is_file($new)) {
                return;
            }
            #如果文件不存在 提示创建
            if (!is_file($file)) {
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
                } else {
                    throw new Exception("您访问的Api控制器类不存在", ErrorCode::$ClassDoesNotExist);
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
                $content = DEBUG ? $content : php_strip_whitespace($new);
                to_mkdir($new, $content, true, true);
            }
        } catch (Exception $e) {
            ERRORCODE($e);
        }
    }
}