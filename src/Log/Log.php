<?php
/**
 * voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Log;


use Voltin\Config\Config;
use Voltin\Core\App;

class Log
{
    /**
     * @var LogInterface
     */
    public static $logger;

    private static $config;

    /**
     * error级别日志
     *
     * @param $message
     * @param string $logFileName
     * @return bool
     */
    public static function error($message, $logFileName = '')
    {
        return self::record($message, $logFileName, 'error');
    }

    /**
     * @param $message
     * @param string $logFileName
     * @return bool
     */
    public static function info($message, $logFileName = '')
    {
        return self::record($message, $logFileName, 'info');
    }

    /**
     * @param $message
     * @param string $logFileName
     * @return bool
     */
    public static function debug($message, $logFileName = '')
    {
        return self::record($message, $logFileName, 'debug');
    }

    /**
     * @param $message
     * @param string $logFileName
     * @return bool
     */
    public static function exception($message, $logFileName = '')
    {
        return self::record($message, $logFileName, 'exception');
    }

    /**
     * 记录日志
     *
     * @param $message
     * @param string $logFileName
     * @param string $level 日志级别 error|debug|exception
     * @return bool
     */
    public static function record($message, $logFileName = '', $level = '')
    {
        $logger = self::getLogger();
        if ($logger === false) {
            return false;
        }
        return $logger->record($message, $logFileName, $level);
    }

    /**
     * 获取记录器
     *
     * @return bool|LogInterface
     */
    private static function getLogger()
    {
        if (self::$config === null) {
            self::$config = Config::runtime('log');
        }

        //当日志写入功能关闭时
        if(self::$config['record'] === false){
            return false;
        }

        if (self::$logger === null) {
            self::$logger = App::di()->get("Voltin\\Log\\Handler\\" . self::$config['driver']);
        }
        return self::$logger;
    }
}
