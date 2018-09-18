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

use this7\routes\build\base;

class routes {

    /**
     * 初始APP核心
     * @var [type]
     */
    protected static $app;

    /**
     * 静态链接
     * @var [type]
     */
    protected static $link;

    public function __construct($app = '') {
        self::$app = $app;
    }
    /**
     * 单例调用
     * @return [type] [description]
     */
    protected static function single() {
        if (!self::$link) {
            self::$link = new base(self::$app);
        }
        return self::$link;
    }

    public function __call($method, $params) {
        return call_user_func_array([self::single(), $method], $params);
    }

    public static function __callStatic($name, $arguments) {
        return call_user_func_array([static::single(), $name], $arguments);
    }
}