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

\think\Console::addDefaultCommands([
    "think\\queue\\command\\Work",
    "think\\queue\\command\\Restart",
    "think\\queue\\command\\Listen",
    "think\\queue\\command\\Subscribe"
]);

if (!function_exists('queue')) {

    /**
     * 添加到队列
     *
     * @param        $job
     * @param string $data
     * @param int    $delay
     * @param null   $queue
     */
    function queue($job, $data = '', $delay = 0, $queue = null) {
        if ($delay > 0) {
            \think\Queue::later($delay, $job, $data, $queue);
        } else {
            \think\Queue::push($job, $data, $queue);
        }
    }
}

if (!function_exists('msectime')) {
    /**
     * 取毫秒级时间戳，默认返回普通秒级时间戳 time() 及 3 位长度毫秒字符串
     *
     * @param int  $msec_length 毫秒长度，默认 3
     * @param int  $delay 是否延迟，传入延迟秒数，默认 0
     * @param int  $random_length 添加随机数长度，默认 0
     * @param bool $dot 随机是否存入小数点，默认 false
     * @return string
     */
    function msectime($msec_length = 3, $delay = 0, $random_length = 0, $dot = false) {
        list($msec, $sec) = explode(' ', microtime());
        $rand = $random_length > 0 ?
            number_format(
                mt_rand(1, (int)str_repeat('9', $random_length))
                * (float)('0.' . str_repeat('0', $random_length - 1) . '1'),
                $random_length,
                '.',
                '') : '';
        $msectime = sprintf('%.0f', (floatval($msec) + floatval($sec) + $delay) * pow(10, $msec_length));
        return $dot ? $msectime . '.' . substr($rand, 2) : $msectime . substr($rand, 2);
    }
}
