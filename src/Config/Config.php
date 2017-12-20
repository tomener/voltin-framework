<?php
/**
 * voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Config;


class Config
{
    /**
     * @var array 配置数据集
     */
    protected static $data = [];

    /**
     * @var array 运行时配置
     */
    protected static $runtime = [];

    /**
     * 获取配置项，没有返回null
     *
     * @param string $index 配置项，最多支持三级配置（以.号隔开）
     * @return mixed
     */
    public static function get($index)
    {
        $index = explode('.', $index);
        $name = $index[0];
        if (!isset(self::$data[$name])) {
            self::$data[$name] = static::load($name);
        }

        if (isset($index[1]) && isset($index[2])) {
            return isset(self::$data[$name][$index[1]][$index[2]]) ? self::$data[$name][$index[1]][$index[2]] : null;
        } else if (isset($index[1])) {
            return isset(self::$data[$name][$index[1]]) ? self::$data[$name][$index[1]] : null;
        } else if (isset(self::$data[$name])) {
            return self::$data[$name];
        } else {
            return null;
        }
    }

    /**
     * 获取运行时配置项
     *
     * @param string $index
     * @return array|mixed|null
     */
    public static function runtime($index = '')
    {
        $index = explode('.', $index);
        $name = $index[0];
        if (isset($index[1]) && isset($index[2])) {
            return isset(self::$runtime[$name][$index[1]][$index[2]]) ? self::$runtime[$name][$index[1]][$index[2]] : null;
        } else if (isset($index[1])) {
            return isset(self::$runtime[$name][$index[1]]) ? self::$runtime[$name][$index[1]] : null;
        } else if (isset(self::$runtime[$name])) {
            return self::$runtime[$name];
        } else if (empty($name)) {
            return self::$runtime;
        } else {
            return null;
        }
    }

    /**
     * 动态设置配置
     *
     * @param string $index 配置项
     * @param string|array $values 配置值
     * @return bool
     */
    public static function set($index, $values)
    {
        $i = explode('.', $index);
        $name = $i[0];
        if (isset($i[2])) {
            if (is_array($values) && isset(self::$data[$name][$i[1]][$i[2]]) && is_array(self::$data[$name][$i[1]][$i[2]])) {
                self::$data[$name][$i[1]][$i[2]] = array_merge(self::$data[$name][$i[1]][$i[2]], $values);
            } else {
                self::$data[$name][$i[1]][$i[2]] = $values;
            }
        } else if (isset($i[1])) {
            if (is_array($values) && isset(self::$data[$name][$i[1]]) && is_array(self::$data[$name][$i[1]])) {
                self::$data[$name][$i[1]] = array_merge(self::$data[$name][$i[1]], $values);
            } else {
                self::$data[$name][$i[1]] = $values;
            }
        } else if (!empty($name)) {
            if (is_array($values) && isset(self::$data[$name]) && is_array(self::$data[$name])) {
                self::$data[$name] = array_merge(self::$data[$name], $values);
            } else {
                self::$data[$name] = $values;
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * 设置运行时配置
     *
     * @param $index
     * @param string $values
     * @return bool
     */
    public static function setRuntime($index, $values = '')
    {
        $i = explode('.', $index);

        if (isset($i[2])) {
            if (is_array($values) && isset(self::$runtime[$i[0]][$i[1]][$i[2]]) && is_array(self::$runtime[$i[0]][$i[1]][$i[2]])) {
                self::$runtime[$i[0]][$i[1]][$i[2]] = array_merge(self::$runtime[$i[0]][$i[1]][$i[2]], $values);
            } else {
                self::$runtime[$i[0]][$i[1]][$i[2]] = $values;
            }
        } else if (isset($i[1])) {
            if (is_array($values) && isset(self::$runtime[$i[0]][$i[1]]) && is_array(self::$runtime[$i[0]][$i[1]])) {
                self::$runtime[$i[0]][$i[1]] = array_merge(self::$runtime[$i[0]][$i[1]], $values);
            } else {
                self::$runtime[$i[0]][$i[1]] = $values;
            }
        } else if (!empty($i[0])) {
            if (is_array($values) && isset(self::$runtime[$i[0]]) && is_array(self::$runtime[$i[0]])) {
                self::$runtime[$i[0]] = array_merge(self::$runtime[$i[0]], $values);
            } else {
                self::$runtime[$i[0]] = $values;
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * 加载配置文件
     *
     * @param string $config 配置文件名或文件路径
     * @param string $name 配置名称
     * @return array
     */
    public static function load($config, $name = '')
    {
        $config_file = $config;
        if (!is_file($config)) {
            $env_path = !defined('ENV') ? '' : strtolower(ENV) . DS;
            $config_file = ROOT_PATH . 'config' . DS . $env_path . $config . '.config.php';
            if (!is_file($config_file)) {
                $config_file = ROOT_PATH . 'config' . DS . $config . '.config.php';
            }
        }
        $config = include $config_file;
        if (empty($name)) {
            return $config;
        }
        if ($name == 'runtime') {
            foreach ($config as $key => $value) {
                if (isset(self::$runtime[$key]) && is_array(self::$runtime[$key]) && is_array($value)) {
                    self::$runtime[$key] = array_merge(self::$runtime[$key], $value);
                } else {
                    self::$runtime[$key] = $value;
                }
            }
        } else {
            self::$data[$name] = $config;
        }
        return $config;
    }

    /**
     * 判断配置是否存在
     *
     * @param $index
     * @return bool
     */
    public static function has($index)
    {
        return null === static::get($index) ? false : true;
    }
}
