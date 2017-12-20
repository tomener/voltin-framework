<?php
/**
 * voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Core;


use Voltin\Config\Config;

class Router
{
    /**
     * 控制器名称
     *
     * @var string
     */
    private $controller;

    /**
     * 操作名称
     *
     * @var string
     */
    private $action;

    /**
     * @var array
     */
    private $param = [];

    /**
     * 初始化router
     *
     * Router constructor.
     */
    function __construct()
    {
        $this->getRouter();
    }

    /**
     * 获取和设置控制器、操作
     */
    private function getRouter()
    {
        if (IS_CLI) {
            return $this->getCliRouter();
        }

        $param = [];
        $url = Config::runtime('url');
        $controller = $url['c'];
        $action = $url['a'];
        $this->parseModule($url['ext']);

        if (isset($_GET[$url['r']])) {
            $_SERVER['PATH_INFO'] = $_GET[$url['r']];
        }
        if(isset($_SERVER['PATH_INFO'])) {
            $path = trim(trim($_SERVER['PATH_INFO'], $url['ext']), '/');
            $router_config = Config::runtime('router');
            if (is_array($router_config)) {
                foreach ($router_config as $key => $value) {
                    if (!strpos($path, $url['join'])) {
                        continue;
                    }
                    $path = str_replace($key, $value, $path);
                }
            }
            $param = explode($url['join'], $path);

            !empty($param[0]) && $controller = $param[0];
            isset($param[1]) && $action = $param[1];
            $controller_conf = Config::runtime('controller');
            if (isset($controller_conf[$controller . '/' . $action])) {
                $controller = lcfirst($controller) . '\\' . ucfirst($action);
                isset($param[2]) && $action = $param[2];
                $action = isset($param[2]) ? $param[2] : 'index';
                $param = array_slice($param, 3);
            } else {
                $controller = ucfirst($controller);
                $param = array_slice($param, 2);
            }
        }

        $this->setController($controller);
        $this->setAction($action);
        $this->setParam($param);
        return true;
    }

    /**
     * CLI模式获取和设置控制器、操作、参数
     *
     * @return bool
     */
    private function getCliRouter()
    {
        $param = [];
        $url = Config::runtime('url');
        $controller = $url['c'];
        $action = $url['a'];
        $this->parseModule($url['ext']);

        $argv = $_SERVER['argv'];
        if (isset($argv[1])) {
            $router = explode($url['join'], $argv[1]);
            $controller = isset($router[0]) ? $router[0] : $controller;
            $action = isset($router[1]) ? $router[1] : $action;
        }
        array_shift($argv);
        array_shift($argv);

        if (count($argv) > 0) {
            foreach ($argv as $value) {
                $temp = explode('=', $value);
                $param[$temp[0]] = $temp[1];
            }
        }

        $this->setController(ucfirst($controller));
        $this->setAction($action);
        $this->setParam($param);
        return true;
    }

    /**
     * 返回控制器名称
     *
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * 返回action名称
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * 设置controller
     *
     * @param $controller
     */
    private function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * 设置Action
     *
     * @param $action
     */
    private function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @param array $param
     */
    private function setParam(Array $param)
    {
        $this->param = array_merge($this->param, $param);
    }

    /**
     * 模块解析
     *
     * @param $ext
     * @return bool
     */
    private function parseModule($ext)
    {
        $modules = Config::runtime('apps');
        $app = Config::runtime('default_app');
        if ($modules && isset($_SERVER['PATH_INFO'])) {
            $path = explode($ext, $_SERVER['PATH_INFO']);
            if (isset($path[1]) && isset($modules[$path[1]])) {
                $app = $modules[$path[1]];
                $_SERVER['PATH_INFO'] = str_replace('/' . $path[1], '', $_SERVER['PATH_INFO']);
            }
        }
        defined('APP_NAME') || define('APP_NAME', $app);
        $app_config = ROOT_PATH . 'config/app/' . APP_NAME . '.config.php';
        Config::load($app_config, 'runtime');
        return true;
    }
}
