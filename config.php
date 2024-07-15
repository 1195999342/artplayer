<?php
$config =  array (
    'name' => 'kansha播放器', //播放器名称。

    'video' => 'https://res11.bignox.com/player/www/101ae5b183a03384c8005e2a827bc4fc/GDCHDGCCHrh8FpZ.mp4', //无地址引导页背景视频地址。

    'interface' => 'https://json.vipjx.cnow.eu.org/?url=',//JSON接口。

    'Standby' => 'https://proxy.kansha.vip/proxy_url=https://json.vipjx.cnow.eu.org/?url=,https://json.2s0.cn:5678/home/api?type=ys&uid=2673453&key=dghoqstuwxzOTX1256&url=',//备用json接口多个请使用,号隔开。

    'iptime' => '20', //限制每个IP每分钟访问次数。

    'background' => 'artplayer/img/background.jpg',//播放器背景图片。

    'loading' => 'artplayer/img/load.gif',//播放器加载图片。

    'zantingguanggaoqidong' => '0',//暂停播放时的广告启动开关，1为启动，0为关闭。

    'zantingguanggaourl' => 'artplayer/img/guanggao.png',//广告图片地址。

    'zantingguanggaolianjie' => 'https://www.baidu.com',//广告链接地址。

    'danmuqidong' => '1',//播放器弹幕启动开关，参数1为开启，参数0为关闭。

    'dmapi' => 'https://dmku.wevip.cc/?ac=dm&type=xml&url=',//弹幕库地址,可用远程弹幕库需支持xml，例如http://www.baidu.com/dmku/。

    'sendtime' => 3,//发送弹幕的间隔时间限制，单位为秒。

    'pbgjz' => '操ABCDEFGHIJKLMNOPQRSTUVWSYZabcdefghijklmnopqrstuvwsyz',//弹幕敏感关键字限制。

    'redis' => array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => ''), //redis配置，留空则不开启缓存。

);
if (!extension_loaded('redis')) {die('php未安装redis扩展插件');exit;}