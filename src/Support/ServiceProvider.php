<?php
/**
 * voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Support;


use Voltin\Core\Container;

abstract class ServiceProvider
{
    /**
     * @var Container
     */
    protected $di;

    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    abstract public function register();
}
