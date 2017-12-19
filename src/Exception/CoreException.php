<?php
/**
 * Voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Exception;


class CoreException extends \Exception
{
    /**
     * 异常处理
     *
     * @param \Exception $e
     */
    public static function handle($e)
    {
        //记录日志
        $no_log = [40001, 40004];
        $msg = '404页面不存在';
        if (!in_array($e->getCode(), $no_log)) {
            $log = self::buildLog($e);
            Log::write(print_r($log, true), 'Error', 'Exception/' . date('m.d'));
        } else {
            $msg = 'Controller or Action not exists.';
        }
        Response::send(Application::result(404, $msg), 'json');
    }

    /**
     * 获取异常信息
     *
     * @param \Exception $e
     * @param bool $output
     * @return bool|string
     */
    public static function buildHtml($e, $output = false)
    {
        $html = '<div class="exception">';
        $html .= '<p>[' . $e->getCode() . '] Exception in ' . $e->getFile() . ' Line '
            . $e->getLine() . '</p>';
        $html .= '<p>Notice: ' . $e->getMessage() . '</p>';
        if (!$output) {
            return $html;
        } else {
            echo $html;
            return true;
        }
    }

    /**
     * 生成404错误日志
     *
     * @param \Exception $e
     * @return array
     */
    public static function buildLog($e)
    {
        $log = [];
        $log['Message'] = $e->getMessage();
        $log['Code'] = $e->getCode();
        $log['File'] = $e->getFile();
        $log['Line'] = $e->getLine();
        $log['trace'] = explode("\n", $e->getTraceAsString());

        return $log;
    }
}
