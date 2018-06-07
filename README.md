# think5-queue（官方名：think5-queue）

## 安装

> composer require maxsky/think5-queue

> composer require topthink/think-queue（官方）

## 配置

> 配置文件位于 `/application/extra/queue.php` 或 `/config/extra/queue.php`

### 公共配置

```php
return [
    'connector' => 'redis' // 驱动类型，可选择 Redis（默认），sync：同步执行，database：数据库驱动，topthink：Topthink 驱动
                           // 或其他自定义的完整的类名
];
```

### 驱动配置
> 各个驱动的具体可用配置项在 `think\queue\connector` 目录下各个驱动类里的 `options` 属性中，写在上面的 `queue` 配置里即可使用


## 使用 DataBase

> 创建如下数据表

```sql
CREATE TABLE `prefix_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

## 创建任务类

> 单模块项目推荐使用 `app\job` 作为任务类的命名空间，多模块项目可用使用 `app\module\job` 作为任务类的命名空间。也可以放在任意可以自动加载到的地方

任务类不需继承任何类，如果这个类只有一个任务，那么就只需要提供一个 `fire` 方法就可以了，如果有多个小任务，就写多个方法，下面发布任务的时候会有区别  
每个方法会传入两个参数 `think\queue\Job $job`（当前的任务对象）和 `$data`（发布任务时的自定义数据）

还有个可选的任务失败执行的方法 `failed` 传入的参数为 `$data`（发布任务时的自定义数据）

### 下面写两个例子

```php
namespace app\job;

use think\queue\Job;

class Job1 {

    public function fire(Job $job, $data) {
        //....这里执行具体的任务

        if ($job->attempts() > 3) {
            // 通过这个方法可以检查这个任务的重试次数
        }

        // 如果任务执行成功，记得删除任务，否则任务会重复执行，直到达到最大重试次数后失败后执行 failed 方法
        $job->delete();

        // 重新发布任务
        $job->release($delay); // $delay 为延迟时间，单位秒
    }

    public function failed($data) {
        // 任务达到最大重试次数后执行此方法
    }

}
```

```php
namespace app\lib\job;

use think\queue\Job;

class Job2 {

    public function task1(Job $job, $data) {
        
    }

    public function task2(Job $job, $data) {
        
    }

    public function failed($data) {
        
    }

}
```


## 发布任务

```php
think\Queue::push($job, $data = [], $queue = null)

think\Queue::later($delay, $job, $data = [], $queue = null)
```

两个方法，前者是立即执行，后者是在延迟 `$delay` 秒后执行。

`$job` 为任务名。

在单模块中，命名空间为 `app\job` 的任务类，比如上面的例子一，此处填写 `Job1` 类名即可

在多模块中，命名空间为 `app\module\job` 的任务类，此处填写 `model/Job1` 即可

如果不满足上述条件则需要填写完整的类名，比如上面的例子二就需要写完整的类名，如`app\lib\job\Job2`

如果一个任务类里有多个小任务，如上面的例子二，需要用 `@` + 方法名，例如：`app\lib\job\Job2@task1`、`app\lib\job\Job2@task2`

`$data` 为用户自定义数据

`$queue` 队列名称。指定任务在哪个队列上执行，同下面监听队列的时候指定的队列名，可不填。如果设置了队列名称，则下方监听时需带上队列名称参数。


## 主动删除任务（非官方增加）

```php
Queue::remove($jobReturnValue, 'push', $queue);
```

`$jobReturnValue` 为创建任务时得到的返回值。目前即时任务 `push` 返回存入 Redis 的 Json 字符串；`later` 返回长度为 **17** 的毫秒时间戳，用作 Redis ZSet 中的 Score。

`$type` 为需要删除的任务类型，默认为 `push`，可传入 `later`；

`$queue` 为队列名称。

默认情况下删除 `push` 任务只需要传入第一个值即可。删除 `later` 任务第二个值传入 `later` 即可。

> 题外话：讲道理即时任务估计也没什么机会让你删除，这个方法主要用在延时任务上。假设现在的需求是订单支付，一个订单超过 30 分钟未支付将订单状态修改为订单超时。如果用户在 30 分钟内支付，主动删除任务就非常有用了。
> 依照官方的版本，到了 30 分钟还是会去执行任务，当然也可以通过逻辑判断用户已支付并直接删除任务。我认为比较多余，所以添加了这个主动删除的方法。

## 监听任务并执行

```bash
php think queue:work

php think queue:listen
```

两种模式，具体的可选参数可以输入命令加 --help 查看。也阔以戳这里：[官方文档 - 详细介绍 - 命令模式](https://github.com/tp5er/think-queue/tree/master/doc#21-%E5%91%BD%E4%BB%A4%E6%A8%A1%E5%BC%8F)

> 可配合 Supervisor 使用，保证进程不会被杀死

---

下面是我想多说两句的：

> 这儿只能监听单任务 hhh，当初可是把我害得苦...

嗯，如果之前发布任务的时候没有指定队列名称，也就是 `$queue`，并且配置文件也没指定 `default` 值。那么默认的队列名称就叫 `default`

所以我们可以执行：（注意是在项目根目录运行，如果 `php` 命令找不到需要自己配置一下环境变量）

```bash
php think queue:work
```

以上命令仅会执行一次任务。

将命令修改为

```bash
php think queue:work --daemon
```

任务就会被监听，一旦发布 `push` 任务就会立刻执行，`later` 任务到了执行时间也会立即执行。

如果指定了队列名称，例如名称叫 `testJob`

也就是：

```php
// 假设我们的任务叫 Job1，并且传了一个数组过去
think\Queue::push('Job1', ['abc' => 123], 'testJob');
```

那么监听的命令就应该是这样的：

```bash
php think queue:work --queue testJob

# 一直监听加上 --daemon
php think queue:work --queue testJob --daemon
```

注意这只是监听单任务的命令，如果有多个队列需要同时监听，不用同时运行多条命令，这样就行：

```bash
php think queue:work --queue testJob --queue default --queue xxxTask --daemon
```

记得一定要在任务成功后删除任务，失败的任务最好在实际运用中记录起来

当然 Linux 服务器如 CentOS，安装 `Supervisor` 后可以在程序的配置文件里面添加日志记录。

最后在如上的 `Job1` 类的 `fire()` 方法内使用 `print` 等方法打印一下内容，也能写到指定的日志文件内，具体的方法后面空了再写吧。


# 本版本作者话

因为官方那个更新太慢，而且似乎没什么开发进展，所以我就自己捣鼓了一番，当然不够完善，Bug 似乎也有点多。因为我的需求基本都在 Redis 上，所以功能都是围绕 Redis 修改调整的。

更详细的一些说明后面再来补充...Emmm，凌晨还在加班的我...
