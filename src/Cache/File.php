<?php
/**
 * Voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Cache;


class File
{
    protected $options = [
        'expire'        => 0,
        'use_sub_dir'  => false,
        'path_level'    => 0,
        'prefix'        => '',
        'length'        => 0,
        'path'          => CACHE_PATH,
        'data_compress' => false,
    ];

    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->init();
    }

    /**
     * 初始化检查
     * @access private
     * @return boolean
     */
    private function init()
    {
        // 创建项目缓存目录
        if (!is_dir($this->options['path'])) {
            if (!mkdir($this->options['path'], 0755, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $key 缓存变量名
     * @return mixed
     */
    public function get($key)
    {
        $cacheFile = $this->getCacheFile($key);
        if (!is_file($cacheFile)) {
            return false;
        }
        Cache::$readTimes++;
        $content = file_get_contents($cacheFile);
        if (false !== $content) {
            $expire = (int) substr($content, 8, 12);
            if (0 != $expire && time() > filemtime($cacheFile) + $expire) {
                //缓存过期删除缓存文件
                $this->unlink($cacheFile);
                return false;
            }
            $content = substr($content, 20, -3);
            if ($this->options['data_compress'] && function_exists('gzcompress')) {
                //启用数据压缩
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content;
        } else {
            return false;
        }
    }

    /**
     * 写入缓存
     * @access public
     * @param string $key 缓存变量名
     * @param mixed $value  存储数据
     * @param int $expire  有效时间 0为永久
     * @return boolean
     */
    public function set($key, $value, $expire = null)
    {
        Cache::$writeTimes++;
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $cacheFile = $this->getCacheFile($key);
        $data     = serialize($value);
        if ($this->options['data_compress'] && function_exists('gzcompress')) {
            //数据压缩
            $data = gzcompress($data, 3);
        }
        $data   = "<?php\n//" . sprintf('%012d', $expire) . $data . "\n?>";
        $result = file_put_contents($cacheFile, $data);
        if ($result) {
            if ($this->options['length'] > 0) {
                // 记录缓存队列
                $queue_file = dirname($cacheFile) . '/__info__.php';
                $queue      = unserialize(file_get_contents($queue_file));
                if (!$queue) {
                    $queue = [];
                }
                if (false === array_search($key, $queue)) {
                    array_push($queue, $key);
                }

                if (count($queue) > $this->options['length']) {
                    // 出列
                    $key = array_shift($queue);
                    // 删除缓存
                    $this->unlink($this->getCacheFile($key));
                }
                file_put_contents($queue_file, serialize($queue));
            }
            clearstatcache();
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除缓存
     * @access public
     * @param string $key 缓存变量名
     * @return boolean
     */
    public function rm($key)
    {
        return $this->unlink($this->getCacheFile($key));
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear()
    {
        $path = $this->options['path'];
        if ($dir = opendir($path)) {
            while ($file = readdir($dir)) {
                $check = is_dir($file);
                if (!$check) {
                    $this->unlink($path . $file);
                }

            }
            closedir($dir);
            return true;
        }
        return false;
    }

    /**
     * 判断文件是否存在后，删除
     * @param $path
     * @return bool
     * @author byron sampson <xiaobo.sun@qq.com>
     * @return boolean
     */
    private function unlink($path)
    {
        return is_file($path) && unlink($path);
    }

    /**
     * 返回缓存文件路径
     *
     * @param $key
     * @return string
     */
    private function getCacheFile($key)
    {
        $key = md5($key);
        if ($this->options['use_sub_dir']) {
            // 使用子目录
            $dir = '';
            $len = $this->options['path_level'];
            for ($i = 0; $i < $len; $i++) {
                $dir .= $key{$i} . '/';
            }
            if (!is_dir($this->options['path'] . $dir)) {
                mkdir($this->options['path'] . $dir, 0755, true);
            }
            $cacheFile = $dir . $this->options['prefix'] . $key . '.php';
        } else {
            $cacheFile = $this->options['prefix'] . $key . '.php';
        }
        return $this->options['path'] . $cacheFile;
    }
}
