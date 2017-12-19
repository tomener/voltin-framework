# voltin-framework
voltin a Simple Fast Smart PHP FrameWork
  
# 目录结构

```
/voltin                           项目目录(自己项目名称，比如用TimoPHP开发的社区应用，叫TimoSNS，自定义)
   |-app                         应用目录
   |   |-admin                   后台
   |   |-api                     APP接口
   |   |-m                       H5
   |   |-small                   小程序
   |   |_web                     PC端应用
   |   |   |-controller          控制器目录
   |   |   |-[business]          复杂的业务逻辑可以存放在这里，[]表示可选，名称自定义，如business、logic等
   |   |   |-model               单个项目会用到的模型，公共模型放到common/model目录下面
   |   |   |-[service]           定义一些单个项目需要用到的底层服务（可选、可自定义名称）
   |   |   |-template            模版目录
   |   |   |   |-default         默认主题
   |   |   |   |   |-Index
   |   |   |   |   |-Space
   |   |   |   |   |-default.layer.php   layout布局
   |   |   |   |-win10           一个win10的扁平化主题
   |   |   |-[view]              视图目录，可以封装一些方法供模版中使用（可选）
   |   |   |_config.php          项目配置文件
   |-business                    公共业务逻辑
   |-cache                       运行时缓存目录
   |-[common]                    公共类库目录
   |   |-weChat                  微信消息处理
   |   |-middleware              中间件
   |   |-provider                服务提供者目录
   |-component                   组件目录
   |-config                      公共配置目录
   |-logs                        debug日志目录
   |-model                       公共模型目录
   |-public                      WEB目录（对外访问目录）名称自定义，如wwwroot、public
   |   |-admin                   admin应用目录
   |   |-api                     app
   |   |-m                       h5
   |   |-small                   小程序
   |   |_web                     pc端
   |   |   |-static              静态资源目录
   |   |   |   |-css
   |   |   |   |-images
   |   |   |   |-js
   |   |   |   |_lib             js第三方库
   |   |   |_index.php           web应用入口文件
   |   |-wx                      微信
   |-send                        推送（微信、小程序、android、IOS）
   |-service                     公共服务目录
   |-task                        异步任务
   |-vendor
   |-bootstrap.php               整个项目的启动文件
   |_composer.json
 ```       

## 入口模式

分为`多入口`和`单一入口`

##### 多入口
一个应用一个入口，默认

##### 单一入口
所有应用共用一个入口

