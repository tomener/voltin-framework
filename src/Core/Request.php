<?php
/**
 * voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Core;


use Voltin\Exception\CoreException;

class Request
{
    protected static $instance;

    /**
     * 实例化类
     *
     * @return Request
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * 当前scriptFile的路径
     *
     * @return string
     * @throws CoreException
     */
    public function getScriptFilePath()
    {
        if (($scriptName = static::server('SCRIPT_FILENAME')) == null) {
            throw new CoreException('determine the entry script URL failed.');
        }

        return realpath(dirname($scriptName));
    }

    /**
     * 获取GET请求参数
     *
     * @param null $key
     * @param null $default
     * @param null $filter
     * @return mixed|null
     */
    public static function get($key = null, $default = null, $filter = null)
    {
        if (!$key) {
            return $_GET;
        }

        $value = isset($_GET[$key]) ? (!is_array($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key]) : $default;

        $filter && $value = call_user_func($filter, $value);

        return $value;
    }

    /**
     * 获取POST请求参数
     *
     * @param null $key
     * @param null $default
     * @param null $filter
     * @return mixed|null|string
     */
    public static function post($key = null, $default = null, $filter = null)
    {
        if (!$key) {
            return $_POST;
        }
        if (!isset($_POST[$key])) {
            return $default;
        }

        $value = !is_array($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];

        $filter && $value = call_user_func($filter, $value);

        return $value;
    }

    /**
     * 取得$_SERVER全局变量的值
     *
     * @param null $key
     * @param null $default
     * @return null
     */
    public static function server($key = null, $default = null)
    {
        if (!$key) {
            return $_SERVER;
        }
        return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
    }

    /*
     * 取得$_ENV全局变量的值
     */
    public static function env($key = '', $default = null)
    {
        if (!$key) {
            return $_ENV;
        }
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }

    /**
     * 是否GET请求
     *
     * @return bool
     */
    public static function isGet()
    {
        return static::server('REQUEST_METHOD') == 'GET';
    }

    /**
     * 是否POST请求
     *
     * @return bool
     */
    public static function isPost()
    {
        return static::server('REQUEST_METHOD') == 'POST';
    }

    /**
     * 是否AJAX请求
     *
     * @return bool
     */
    public static function isAjax()
    {
        return static::server('HTTP_X_REQUESTED_WITH') == 'xmlhttprequest';
    }

    /**
     * 获取客户端IP
     *
     * @return null|string
     */
    public static function getClientIP()
    {
        $ip = null;
        $remote_address = static::server('REMOTE_ADDR');

        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
            $ip = explode(',', $ip);
            $ip = trim(array_pop($ip));
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (!empty($remote_address) && strcasecmp($remote_address, 'unknown')) {
            $ip = $remote_address;
        }

        $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
        return $ip;
    }

    /**
     * 获取请求头信息
     *
     * @return array|false|string
     */
    public static function getHeaders()
    {
        $headers = [];
        if (!function_exists('getallheaders')) {
            foreach ($_SERVER as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                {
                    $headers[strtolower(substr($name, 5))] = $value;
                }
            }
        } else {
            $headers = getallheaders();
        }
        return $headers;
    }
}
