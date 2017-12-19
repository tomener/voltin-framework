<?php
/**
 * Voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Core;


use Voltin\Config\Config;

class Engine
{
    /**
     * @var App
     */
    private $app;

    /**
     * @var Router
     */
    private $router;

    public function __construct()
    {
        spl_autoload_register([$this, 'loadClass']);

        $this->loadConfig();

        defined('CACHE_PATH') || define('CACHE_PATH', Config::runtime('cache.path'));

        $this->router = new Router();
    }

    /**
     * 启动应用
     */
    public function start()
    {
        $controller = $this->router->getController();
        $action = $this->router->getAction();
        $params = $this->router->getParam();
        $this->run($controller, $action, $params);
    }

    /**
     * 运行
     *
     * @param $controller
     * @param $action
     * @param array $params
     * @throws CoreException
     */
    public function run($controller, $action, $params = [])
    {
        $di = Application::di();
        Application::iniSet($controller, $action, $params);
        $appClassName = 'app\\' . APP_NAME . '\\controller\\' . $controller;
        $class_file = ROOT_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $appClassName) . '.php';

        if (!file_exists($class_file)) {
            throw new CoreException('class ' . $class_file . ' not found.', 40004);
        }

        $this->loadProvider($di);

        $this->app = $di->get($appClassName);
        if (!method_exists($this->app, $action)) {
            throw new CoreException('Controller ' . $controller . ' has not method ' . $action, 40001);
        }

        $data = call_user_func_array([$this->app, $action], $params);
        if ($data != null) {
            Response::send($data, Response::type());
        }
    }

    /**
     * 加载配置文件
     */
    private function loadConfig()
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
     *
     * @param Container $di
     */
    private function loadProvider(Container $di)
    {
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
     * @return bool
     * @throws CoreException
     */
    private function loadClass($class_name)
    {
        $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        $pos = strpos($class_name, DIRECTORY_SEPARATOR);
        $space = substr($class_name, 0, $pos);

        if ($space == 'Timo') {
            $class_name = substr($class_name, $pos + 1);
            $class_file = LIBRARY_PATH . $class_name . '.php';
        } else {
            $class_file = ROOT_PATH . $class_name . '.php';
        }

        if (file_exists($class_file)) {
            require $class_file;
            return true;
        }

        $class_file = ROOT_PATH . 'lib' . DIRECTORY_SEPARATOR . $class_name . '.php';

        if (!file_exists($class_file)) {
            //throw new Exception('class ' . $class_file . ' not found.', 404);
        } else {
            require $class_file;
        }
        return true;
    }
}
