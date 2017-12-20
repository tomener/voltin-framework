<?php
/**
 * voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Log\Handler;


use Voltin\Config\Config;
use Voltin\Core\App;
use Voltin\Core\Request;
use Voltin\Log\LogInterface;

class File implements LogInterface
{
    /**
     * 记录日志
     *
     * @param $message
     * @param string $logFileName
     * @param string $level
     * @return bool
     */
    public function record($message, $logFileName = '', $level = '')
    {
        $logFilePath = static::getLogFilePath($logFileName, $level);
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
            $log_str = '';
            foreach ($message as $key => $val) {
                $log_str .= $key . '=' . (!is_array($val) ? $val : json_encode($val)) . ' ';
            }
            $log_str = rtrim($log_str);
            $message = $log_str;
        }

        return sprintf("[%s %s %s] %s\n", date('Y-m-d H:i:s'), $client_ip, $router, $message);
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
     * @param string $logFileName 日志文件名
     * @param string $level 日志级别
     * @return string
     *
     * @example
     *
     * $this->getLogFilePath('sql');
     * $this->getLogFilePath('2012-11/23');
     */
    private static function getLogFilePath($logFileName = '', $level = '')
    {
        $logFileName = preg_replace("@\/|\\\@", DS, $logFileName);
        //组装日志文件路径
        $path = Config::runtime('log.path') . APP_NAME . DS;
        if (empty($logFileName)) {
            $path .= date('Y-m') . DS . date('d');
        } else {
            if (strpos($logFileName, DS) !== false) {
                $path .= $logFileName;
            } else {
                $path .= date('Y-m') . DS . $logFileName;
            }
        }
        if (!empty($level)) {
            $path .= '.' . $level;
        }
        $path .= '.log';
        return $path;
    }
}
