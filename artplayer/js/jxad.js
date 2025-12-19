function get_JxUrl(urlo) {
    let type = '';
    let host = urlo.replace(/.*?:?\/\//g, '').split('/')[0];

    if (host.includes('bf')) type = 'bf';
    else if (host.includes('lz')) type = 'lz';
    else if (host.includes('ff')) type = 'ff';

    if (!type) return urlo; //无需处理的直接返回

    try {
        let urlData = getM3u8Content(urlo);
        urlo = urlData.url;
        let content = urlData.content;

        let tsListArr = content.split('\n');

        // 专门处理 ff 类型：删除第二个和第三个 EXT-X-DISCONTINUITY 之间的内容
        if (type === 'ff') {
            let discontCount = 0;
            let removeStart = -1;
            let removeEnd = -1;

            for (let i = 0; i < tsListArr.length; i++) {
                let line = tsListArr[i];
                if (line.includes('EXT-X-DISCONTINUITY')) {
                    discontCount++;
                    if (discontCount === 2 && removeStart === -1) {
                        removeStart = i;
                    } else if (discontCount === 3 && removeEnd === -1) {
                        removeEnd = i;
                        break;
                    }
                }
            }

            // 如果找到了第二个和第三个 EXT-X-DISCONTINUITY，则删除它们之间的所有行（包括这两个标签）
            if (removeStart !== -1 && removeEnd !== -1) {
                tsListArr.splice(removeStart, removeEnd - removeStart + 1);
            }
        } else {
            // 其他类型的原有处理逻辑
            let tsIndexArr = []; // 需要删除的行
            let lastNum = 0;     // 修复点：初始化为 0

            for (let i = 0; i < tsListArr.length; i++) {
                let line = tsListArr[i];
                if (!line.endsWith('.ts')) continue;

                if (type === 'bf') {
                    if (line.includes('adjump')) {
                        tsIndexArr.push(i, i - 1);
                    }
                } else if (type === 'lz') {
                    // 取 ts 文件名后 6 位数字
                    let num = parseInt(line.replace(/.*?([0-9]{6})\.ts$/, '$1'));
                    if (!isNaN(num)) {
                        if (num !== 0 && num !== lastNum + 1) {
                            tsIndexArr.push(i - 1, i);
                            continue
                        }
                        lastNum = num;
                    } else {
                    }
                }
            }

            // 去重 + 排序 ↓↓↓
            tsIndexArr = [...new Set(tsIndexArr)].sort((a, b) => b - a);
            tsIndexArr.forEach(index => {
                if (index >= 0 && index < tsListArr.length) {
                    tsListArr.splice(index, 1);
                }
            });
        }

        // 前缀（ts 所在目录）
        let base = urlo.replace(/\/[^\/]*$/, '');

        for (let i = 0; i < tsListArr.length; i++) {
            if (tsListArr[i].endsWith('.ts')) {
                tsListArr[i] = base + '/' + tsListArr[i];
            }
        }

        let newM3u8 = tsListArr.join('\n');
        let blob = new Blob([newM3u8], {type: 'application/vnd.apple.mpegURL'});
        return URL.createObjectURL(blob);

    } catch (e) {
        console.log('去广告失败，返回原地址：', e);
        return urlo;
    }
}

//递归获取真实 m3u8 内容
function getM3u8Content(url) {
    let xhr = new XMLHttpRequest();
    xhr.open('GET', url, false);
    xhr.send();
    if (xhr.status === 200) {
        let text = xhr.responseText;
        let match = text.match(/.*?\.m3u8/);
        if (match) {
            url = url.replace(/\/[^\/]*$/, '') + '/' + match[0];
            return getM3u8Content(url);
        }
        return {url: url, content: text};
    }
    return {url: url, content: ''};
}