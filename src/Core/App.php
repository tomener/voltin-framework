<?php
/**
 * Voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Core;


use Voltin\Config\Config;
use Voltin\Exception\CoreException;

class App
{
    /**
     * @var string 控制器名
     */
    protected static $controller;

    /**
     * @var string 动作名称
     */
    protected static $action;

    /**
     * @var array 参数
     */
    protected static $params;

    /**
     * @var Container 依赖注入容器
     */
    protected static $container;

    protected static $instance;

    public function __construct()
    {
        self::definition();
        spl_autoload_register([$this, 'loadClass']);
        self::loadConfig();
        defined('CACHE_PATH') || define('CACHE_PATH', Config::runtime('cache.path'));
        self::loadRouter();
        self::loadProvider();
    }

    /**
     * 运行应用
     *
     * @throws CoreException
     */
    public function run()
    {
        $class_name = 'app\\' . APP_NAME . '\\controller\\' . self::$controller;
        $class_file = ROOT_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';

        if (!file_exists($class_file)) {
            throw new CoreException('class ' . $class_file . ' not found.', 40004);
        }

        $app = App::di()->get($class_name);
        if (!method_exists($app, self::$action)) {
            throw new CoreException('Controller ' . self::$controller . ' has not method ' . self::$action, 40001);
        }

        $data = call_user_func_array([$app, self::$action], self::$params);
        if ($data != null) {
            Response::send($data, Response::type());
        }
    }

    /**
     * 获取控制器名
     *
     * @return string
     */
    public static function controller()
    {
        return static::$controller;
    }

    /**
     * 获取动作名
     *
     * @return string
     */
    public static function action()
    {
        return static::$action;
    }

    /**
     * 获取IOC/DI容器
     *
     * @return Container
     */
    public static function di()
    {
        if (is_null(static::$container)) {
            static::$container = new Container();
            static::$container->instance(['Voltin\Core\Container' => 'di'], static::$container);
        }
        return static::$container;
    }

    /**
     * 返回一个数组或JSON字符串
     *
     * @param int $code
     * @param string $msg
     * @param array $data
     * @param bool $json_encode
     * @return array|string
     */
    public static function result($code = 1, $msg = '', $data = null, $json_encode = false)
    {
        $result = ['code' => $code, 'msg' => $msg];
        if (!is_null($data)) {
            $result['data'] = $data;
        }

        if ($json_encode) {
            $result = json_encode($result);
        }

        return $result;
    }

    /**
     * 加载配置文件
     */
    private static function loadConfig()
    {
        Config::load(FRAME_PATH . 'config/config.php', 'runtime');

        $env_path = !defined('ENV') ? '' : strtolower(ENV) . DS;
        $db_config = ROOT_PATH . 'config' . DS . $env_path . 'db.config.php';

        Config::load($db_config, 'runtime');
        $common_config = ROOT_PATH . 'config' . DS . $env_path . 'common.config.php';
        if (file_exists($common_config)) {
            Config::load($common_config, 'runtime');
        }
    }

    /**
     * 加载服务提供者
     */
    private static function loadProvider()
    {
        $di = self::di();
        if ($providers = Config::runtime('providers')) {
            foreach ($providers as $provider) {
                $provider = $di->get($provider);
                $provider->register();
            }
        }
    }

    /**
     * 自动加载
     *
     * @param $class_name
     * @throws CoreException
     */
    private function loadClass($class_name)
    {
        $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        $class_file = ROOT_PATH . $class_name . '.php';

        if (!file_exists($class_file)) {
            throw new CoreException('[loadClass] class ' . $class_file . ' not found.', 404);
        } else {
            require $class_file;
        }
    }

    /**
     * 路由解析
     */
    protected static function loadRouter()
    {
        $router = new Router();
        self::$controller = $router->getController();
        self::$action = $router->getAction();
        self::$params = $router->getParam();
    }

    /**
     * 应用初始
     */
    private static function definition()
    {
        //版本检查
        version_compare(PHP_VERSION, '7.0.0', '>=') || die('requires PHP 7.0.0+ Please upgrade!');

        //路径常量定义
        defined('DS') || define('DS', DIRECTORY_SEPARATOR);
        defined('ROOT_PATH') || die('[voltin] undefined ROOT_PATH constant.');
        define('APP_DIR_PATH', ROOT_PATH . 'app' . DS);
        define('FRAME_PATH', dirname(dirname(__DIR__)) . DS);
        define('LIBRARY_PATH', FRAME_PATH . 'src' . DS);
        defined('APP_DEBUG') || define('APP_DEBUG', false);

        // 环境常量
        define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
        define('IS_MAC', strstr(PHP_OS, 'Darwin') ? 1 : 0);
        define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);
        define('NOW_TIME', $_SERVER['REQUEST_TIME']);
    }
}
