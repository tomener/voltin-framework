<?php
/**
 * voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Log\Handler;


use Voltin\Log\LogInterface;

class Mongodb implements LogInterface
{
    public function record($message, $logFileName, $level)
    {
        return true;
    }
}
