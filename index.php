<?php


include_once './config.php';
include_once './class/Http.php';
if ($_GET['url'] == '') {
    exit('<body style="display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; text-align: center; color: greenyellow; background-color: black;"><h3>播放地址为空</h3></body>');
}

$address = "";
$url = preg_replace("/url=/", "", $_SERVER["QUERY_STRING"], 1);
//判断url 包含 m3u8 MP4

if (strstr($url, '.m3u8') || strstr($url, '.mp4')) {
    $address = $url;
} else {
    $key = md5($url);
    $cache = "";
    if ($config['redis']['open']) {
        //先查询缓存
        $redis = new Redis();
        $redis->connect($config['redis']['host'], $config['redis']['port']);
        if ($config['redis']['password']) {
            $redis->auth($config['redis']['password']);
        }
        $key = md5($url);
        $cache = "";
        if ($config['redis']['open']) {
            $cache = $redis->get($key);
        }
    }
    if ($cache) {
        $address = $cache;
    } else {
        $u = $config['interface'] . $url;
        $result = Http::geturl($u);
        if ($result["code"] == 200) {
            $address = $result["url"];
            if($address){
                $address = Http::getRedirectedUrl($address);
            }
        }

        if ($address == "" && $config['Standby']) {
            $s = explode(",", $config['Standby']);
            foreach ($s as $v) {
                $api = "$v$url";
                $result = Http::geturl($api);
                if ($result['code'] == 200) {
                    $address = $result["url"];
                    if($address){
                        $address = Http::getRedirectedUrl($address);
                    }
                    break;
                }
            }
        }
        if ($address == "") {
            //返回 html 解析失败 上下左右居中
            header('Content-Type: text/html;charset=utf-8');
            exit('<body style="display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; text-align: center; color: greenyellow; background-color: black;"><h3>解析失败,请更换线路！</h3></body>');
        } else {
            //缓存
            $redis->set($key, $address, 60 * 60 * 3);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $config['name']; ?></title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="referrer" content="no-referrer">
    <meta http-equiv="Access-Control-Allow-Origin" content="*"/>
    <meta http-equiv="Access-Control-Allow-Credentials" content="*"/>
    <meta name="viewport"
          content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no"/>
    <style>
        body, html {
            font: 24px "Microsoft YaHei", Arial, Lucida Grande, Tahoma, sans-serif;
            width: 100%;
            height: 100%;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
            overflow-y: hidden;
            background-color: black;
        }

        .artplayer-app {
            aspect-ratio: 16/9;
        }


    </style>
    <script src="artplayer/js/jquery.min.js"></script>
    <script src="artplayer/js/jxad.js"></script>
    <script src="artplayer/js/artplayer.js"></script>
    <script src="artplayer/js/artplayer-plugin-danmuku.js"></script>
    <script src="artplayer/js/hls.min.js"></script>
</head>
<body>
<div class="artplayer-app"></div>

<script>
    var type = "";
    var adressO = '<?php echo $address ?>';

    var url = '<?php echo $url ?>';
    var adress = get_JxUrl(adressO);
    //获取重定向后的地址
    //adress = getRedirectedUrl(adress);
    //如果以.m3u8结尾，则播放m3u8
    if (adress.endsWith('.m3u8')) {
        type = 'm3u8';
    }else if (adress.endsWith('.mp4')) {
        type ='mp4';
    }
    var danmuku = '<?php echo $config['dmapi'] . $url ?>';
    const art = new Artplayer({
        type: type,
        customType: {
            m3u8: playM3u8,
        },
        id: '<?php echo md5($url) ?>',
        container: '.artplayer-app',
        url: adress,
        fastForward: true,
        lock: true,
        //autoSize: true,
        airplay: true,
        playsInline: true,
        fullscreen: true,
        //fullscreenWeb: true,
        autoOrientation: true,
        autoplay: true,
        autoMini: true,
        flip: true,
        playbackRate: true,
        aspectRatio: true,
        screenshot: true,
        hotkey: true,
        pip: true,
        setting: true,
        plugins: [
            artplayerPluginDanmuku({
                danmuku: danmuku,
                // 以下为非必填
                speed: 5, // 弹幕持续时间，范围在[1 ~ 10]
                margin: [10, '25%'], // 弹幕上下边距，支持像素数字和百分比
                opacity: 1, // 弹幕透明度，范围在[0 ~ 1]
                color: '#FFFFFF', // 默认弹幕颜色，可以被单独弹幕项覆盖
                mode: 0, // 默认弹幕模式: 0: 滚动，1: 顶部，2: 底部
                modes: [0, 1, 2], // 弹幕可见的模式
                fontSize: 25, // 弹幕字体大小，支持像素数字和百分比
                antiOverlap: true, // 弹幕是否防重叠
                synchronousPlayback: false, // 是否同步播放速度
                mount: undefined, // 弹幕发射器挂载点, 默认为播放器控制栏中部
                heatmap: true, // 是否开启热力图
                width: 512, // 当播放器宽度小于此值时，弹幕发射器置于播放器底部
                points: [], // 热力图数据
                filter: (danmu) => danmu.text.length <= 100, // 弹幕载入前的过滤器
                beforeVisible: () => true, // 弹幕显示前的过滤器，返回 true 则可以发送
                visible: true, // 弹幕层是否可见
                emitter: true, // 是否开启弹幕发射器
                maxLength: 200, // 弹幕输入框最大长度, 范围在[1 ~ 1000]
                lockTime: 5, // 输入框锁定时间，范围在[1 ~ 60]
                theme: 'dark', // 弹幕主题，支持 dark 和 light，只在自定义挂载时生效
                OPACITY: {}, // 不透明度配置项
                FONT_SIZE: {}, // 弹幕字号配置项
                MARGIN: {}, // 显示区域配置项
                SPEED: {}, // 弹幕速度配置项
                COLOR: [], // 颜色列表配置项

                // 手动发送弹幕前的过滤器，返回 true 则可以发送，可以做存库处理
                beforeEmit(danmu) {
                    return new Promise((resolve) => {
                        c$.ajax({
                            type: "post",
                            url: danmuku,
                            contentType: "application/json",
                            data: JSON.stringify({
                                player: url,
                                text: danmu.text,
                                color: danmu.color,
                                time: danmu.time,
                            })
                        });
                        resolve(true);
                    });
                },

            }),
        ],
    });

    function playM3u8(video, url, art) {
        if (Hls.isSupported()) {
            if (art.hls) art.hls.destroy();
            const hls = new Hls();
            hls.loadSource(url);
            hls.attachMedia(video);
            art.hls = hls;
            art.on('destroy', () => hls.destroy());
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = url;
        } else {
            art.notice.show = 'Unsupported playback format: m3u8';
        }
    }

    function getRedirectedUrl(url) {
        var redirectedUrl = null;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, false); // 设置为同步请求
        xhr.setRequestHeader('Range', 'bytes=0-0'); // 请求只获取第一个字节

        xhr.onreadystatechange = function () {
            console.log("xhr", xhr)
            if (xhr.readyState === 4) {
                redirectedUrl = xhr.responseURL
            }
        };
        xhr.send();
        console.log('redirectedUrl', redirectedUrl);
        return redirectedUrl;
    }

    art.on('ready', () => {
        art.play();
    });
</script>
</body>
</html>
