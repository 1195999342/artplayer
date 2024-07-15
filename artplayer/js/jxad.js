//获取资源去广告的m3u8地址
function get_JxUrl(urlo) {
    let type = ''
    let host = urlo.replace(/.*?:?\/\//g, '').split('/')[0]
    if (host) {
        if (host.indexOf('bf') > -1) {
            type =  'bf'
        } else if (host.indexOf('lz') > -1) {
            type = 'lz'
        } else if (host.indexOf('ff') > -1) {
            type = 'ff'
        }
    }
    if (type === '') {
        return urlo
    }
    try {
        let urlData = getM3u8Content(urlo)
        urlo = urlData.url
        let tsList = urlData.content
        //循环内容 去除不连续的ts文件
        //记录不连续ts的下标
        let tsIndexArr = []
        //记录ts的文件名后6位
        let tsName = ''
        let tsListArr = tsList.split('\n')
        for (let i = 0; i < tsListArr.length; i++) {
            if (tsListArr[i].indexOf('.ts') > -1) {
                if (type === 'bf') {
                    //爆风资源 如果包含adjump
                    if (tsListArr[i].indexOf('adjump') > -1) {
                        tsIndexArr.push(i)
                        tsIndexArr.push(i - 1)
                    }
                } else if (type === 'lz' || type === 'ff') {
                    //量子 非凡资源 去掉后缀后的文件名后6位
                    let name = tsListArr[i].replace('.ts', '').substr(-6)
                    //转数字
                    name = parseInt(name)
                    //如果名字转成数字后不是连续的并且不等于0，说明是不连续的ts文件
                    if (name !== 0 && name - tsName !== 1) {
                        tsIndexArr.push(i - 1)
                        tsIndexArr.push(i)
                    } else {
                        tsName = name
                    }
                }
            }
        }
        //去除不连续的ts文件
        for (let i = tsIndexArr.length - 1; i >= 0; i--) {
            tsListArr.splice(tsIndexArr[i], 1)
        }
        //生成新的m3u8文件 每个ts前面加上请求地址
        for (let i = 0; i < tsListArr.length; i++) {
            if (tsListArr[i].indexOf('.ts') > -1) {
                tsListArr[i] = urlo.replace(/\/[^\/]*$/, '') + '/' + tsListArr[i]
            }
        }
        let str = tsListArr.join('\n');
        //生成新的m3u8临时文件
        let blob = new Blob([str], {type: 'application/x-mpegURL'});
        return URL.createObjectURL(blob);
    } catch (e) {
        return urlo
    }
}

//递归获取m3u8文件内容
function getM3u8Content(url) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, false);
    xhr.send();
    if (xhr.status === 200) {
        var content = xhr.responseText;
        var m3u8Url = content.match(/.*?\.m3u8/);
        if (m3u8Url) {
            //替换最后一个/后面的内容
            url = url.replace(/\/[^\/]*$/, '') + '/' + m3u8Url[0]
            return getM3u8Content(url);
        } else {
            return {url: url, content: content};
        }
    }
}