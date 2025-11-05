function get_JxUrl(urlo) {
    try {
        let urlData = getM3u8Content(urlo);
        urlo = urlData.url;
        let content = urlData.content;

        let tsListArr = content.split('\n');
        let toRemove = [];

        for (let i = 0; i < tsListArr.length; i++) {
            let line = tsListArr[i].trim();

            // 发现广告 ts：包含 adjump / ad / adverts
            if (line.endsWith('.ts') && /adjump|ad-|advert|ads|\/ad\//i.test(line)) {
                // 删除当前行
                toRemove.push(i);
                // 前面紧跟的 #EXTINF 一并删掉
                if (i > 0 && tsListArr[i - 1].startsWith('#EXTINF')) {
                    toRemove.push(i - 1);
                }
            }
        }

        // 去重 + 倒序删除，避免索引错乱
        toRemove = [...new Set(toRemove)].sort((a, b) => b - a);
        toRemove.forEach(index => tsListArr.splice(index, 1));

        // 构造 ts 完整路径前缀
        let base = urlo.replace(/\/[^\/]*$/, '');

        for (let i = 0; i < tsListArr.length; i++) {
            if (tsListArr[i].endsWith('.ts')) {
                // 若 ts 已经是绝对路径，则不动
                if (!tsListArr[i].startsWith('http')) {
                    // 去掉开头的 / 避免双斜杠
                    tsListArr[i] = base + '/' + tsListArr[i].replace(/^\//, '');
                }
            }
        }

        let newM3u8 = tsListArr.join('\n');
        let blob = new Blob([newM3u8], { type: 'application/vnd.apple.mpegURL' });
        return URL.createObjectURL(blob);

    } catch (e) {
        console.log('去广告失败，返回原地址：', e);
        return urlo;
    }
}


// 递归获取真实 m3u8 内容
function getM3u8Content(url) {
    let xhr = new XMLHttpRequest();
    xhr.open('GET', url, false);
    xhr.send();
    if (xhr.status === 200) {
        let text = xhr.responseText;
        let match = text.match(/\/?[^"\s']+\.m3u8/);
        if (match) {
            url = url.replace(/\/[^\/]*$/, '') + '/' + match[0].replace(/^\//, '');
            return getM3u8Content(url);
        }
        return { url: url, content: text };
    }
    return { url: url, content: '' };
}
