<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think;

use Composer\Script\Event;
use Composer\Installer\InstallerEvent;
use Composer\Installer\PackageEvent;
use think\helper\Str;
use think\queue\Connector;

/**
 * Class Queue
 *
 * @package think\queue
 *
 * @method static push($job, $data = '', $queue = null)
 * @method static later($delay, $job, $data = '', $queue = null)
 * @method static remove($condition, $type = 'push', $queue = null)
 * @method static pop($queue = null)
 * @method static marshal()
 */
class Queue {
    /** @var Connector */
    protected static $connector;

    private static function buildConnector() {
        $options = Config::get('queue');
        $type = !empty($options['connector']) ? $options['connector'] : 'Sync';

        if (!isset(self::$connector)) {

            $class = false !== strpos($type, '\\') ? $type : '\\think\\queue\\connector\\' . Str::studly($type);

            self::$connector = new $class($options);
        }
        return self::$connector;
    }

    public static function __callStatic($name, $arguments) {
        return call_user_func_array([self::buildConnector(), $name], $arguments);
    }

    /**
     * The method will run when this package is installed. Please ignore this method.
     */
    public static function postPackageInstall(Event $event) {
        $rootPath = $event->getComposer()->getConfig()->get('vendor-dir') . '/../';
        if (is_dir($rootPath . 'config') && file_exists($rootPath . 'config/config.php')) {
            if (!is_dir($rootPath . 'config/extra')) {
                if (!mkdir($rootPath . 'config/extra')) {
                    echo "Create config/extra folder failed, please create manually.\n";
                }
            }
            if (is_writable($rootPath . 'config/extra')) {
                if (!file_exists($rootPath . 'config/extra/queue.php')) {
                    copy(__DIR__ . '/config.php', $rootPath . 'config/extra/queue.php');
                } else {
                    echo "config/extra/quque.php already exist.\n";
                }
            } else {
                echo "config/extra folder not writable, please check folder permission.\n";
            }
        } else {
            $appPath = is_dir($rootPath . 'application') ? $rootPath . 'app/' : $rootPath . 'app/';
            if (file_exists($appPath . 'config.php')) {
                if (!mkdir($appPath . 'extra')) {
                    echo "Create extra folder failed, please create manually.\n";
                } elseif (is_writable($appPath . 'extra')) {
                    if (!file_exists($appPath . 'extra/queue.php')) {
                        copy(__DIR__ . '/config.php', $appPath . 'extra/queue.php');
                    } else {
                        echo "extra/quque.php already exist.\n";
                    }
                } else {
                    echo "extra folder not writable, please check folder permission.\n";
                }
            }
        }
    }
}
