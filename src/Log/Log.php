<?php
/**
 * Created by gumaor.com
 * User: tommy
 */

namespace Voltin\Log;


use Voltin\Config\Config;
use Voltin\Core\App;
use Voltin\Core\Request;

class Log
{
    public static function instance()
    {

    }

    public static function record($message, $logFileName = null)
    {
        //当日志写入功能关闭时
        if(Config::runtime('log.record') === false){
            return true;
        }

        $logFilePath = static::getLogFilePath($logFileName);
        static::makeLogFolder($logFilePath);

        //日志内容
        $message = static::buildLogContent($message);

        return error_log($message, 3, $logFilePath);
    }

    /**
     * 创建日志内容
     *
     * @param $message
     * @return string
     */
    private static function buildLogContent($message)
    {
        $router = App::controller() . '/' . App::action();
        $client_ip = Request::getClientIP();
        if (is_array($message)) {
            $message = json_encode($message);
        }

        return sprintf('[%s %s %s] %s', date('Y-m-d H:i:s'), $client_ip, $router, $message);
    }

    /**
     * 创建日志目录
     *
     * @param $logFilePath
     */
    private static function makeLogFolder($logFilePath)
    {
        $logDir = dirname($logFilePath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    /**
     * 获取当前日志文件名
     *
     * @access private
     * @param null $logFileName 日志文件名
     * @param string $prefix 文件名前缀
     * @return string
     *
     * @example
     *
     * $this->getLogFilePath('sql');
     * $this->getLogFilePath('2012-11/23');
     */
    private static function getLogFilePath($logFileName = null, $prefix = '')
    {
        $logFileName = preg_replace("@\/|\\\@", DS, $logFileName);
        //组装日志文件路径
        $path = Config::runtime('log.path');
        if (IS_CLI) {
            $path .= 'cli' . DS;
        }
        if (!$logFileName) {
            $path .= date('Y-m') . DS . $prefix . date('d');
        } else {
            if (strpos($logFileName, DS) !== false) {
                $path .= $logFileName;
            } else {
                $path .= date('Y-m') . DS . $prefix . $logFileName;
            }
        }
        $path .= '.log';
        return $path;
    }
}
