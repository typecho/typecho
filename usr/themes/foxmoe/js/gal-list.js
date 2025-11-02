function copyToClipboard(text) {
    text = "https://pan.baidu.com/s/1j-1CsQcoTViN9MObDHmy4A?pwd=2233 提取码: 2233 " + text + "【foxmoe.top】";
    navigator.clipboard.writeText(text).then(() => {
       if (window.App && typeof App.toast === 'function') App.toast('已复制到剪贴板');
    }).catch(err => {
        console.error('复制失败: ', err);
    });
}

function parseGameData(rawContent) {
    const games = [];

    const gameBlockRegex = /\[game\](.*?)\[endgame\]/gs;
    let gameMatch;
    
    while ((gameMatch = gameBlockRegex.exec(rawContent)) !== null) {
        const gameBlock = gameMatch[1];
        const gameData = {};
        
        const fileNameMatch = gameBlock.match(/\[fileName:\s*(.*?)\]/);
        const originNameMatch = gameBlock.match(/\[originName:\s*(.*?)\]/);
        const cnNameMatch = gameBlock.match(/\[cnName:\s*(.*?)\]/);
        const tagsMatch = gameBlock.match(/\[tags:\s*(.*?)\]/);
        const studioMatch = gameBlock.match(/\[studio:\s*(.*?)\]/);
        const imageMatch = gameBlock.match(/\[image:\s*(.*?)\]/);


        if (fileNameMatch && fileNameMatch[1]) {
            gameData.fileName = fileNameMatch[1].trim();
        }
        if (originNameMatch && originNameMatch[1]) {
            gameData.originName = originNameMatch[1].trim();
        }
        if (cnNameMatch && cnNameMatch[1]) {
            gameData.cnName = cnNameMatch[1].trim();
        }
        if (tagsMatch && tagsMatch[1]) {
            gameData.tags = tagsMatch[1].split(',').map(tag => tag.trim()).filter(tag => tag);
        }
        if (studioMatch && studioMatch[1]) {
            gameData.studio = studioMatch[1].trim();
        }
        if (imageMatch && imageMatch[1]) {
            gameData.image = imageMatch[1].trim();
        }

        if (gameData.fileName && gameData.fileName !== 'NULL') {
            games.push(gameData);
        }
    }
    
    return games;
}

function renderGameList(games, container) {
    const placeholder = (window.THEME_URL || '') + 'img/placeholder.jpg';
    const gamesHTML = games.map(game => `
        <div class="game-card" onclick="copyToClipboard('${(game.fileName||'').replace(/'/g, "\\'")} ${(game.cnName||'').replace(/'/g, "\\'")} ${(game.originName||'').replace(/'/g, "\\'")}')">
            <div class="game-image">
                <img src="${game.image || placeholder}" 
                     alt="${(game.cnName||'').replace(/"/g, '&quot;')}封面" 
                     loading="lazy"
                     onerror="this.src='${placeholder}'">
            </div>
            <div class="game-info">
                <p class="game-filename">${game.fileName || ''}</p>
                <p class="game-title">${game.cnName || ''}</p>
                <p class="game-subtitle">${game.originName || ''}</p>
                <p class="game-studio">${game.studio || ''}</p>
                ${game.tags && game.tags.length > 0 ? `
                <div class="game-tags">
                    ${game.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
                </div>` : ''}
            </div>
        </div>
    `).join('');
    container.innerHTML = "<div><h3>已收录数量: "+ games.length +"</h3></div><div class=\"games-grid\">" + gamesHTML + "</div>";
}

function initGameList() {
    const galListContainer = document.getElementById('gal-list');
    const codeBlocks = document.querySelectorAll('pre code');

    let gameDataFound = false;
    for (const codeBlock of codeBlocks) {
        codeBlock.style.display = 'none';
        const content = codeBlock.textContent;
      
        if (content.includes('[game]') && content.includes('[endgame]')) {
            gameDataFound = true;

            try {
                const games = parseGameData(content);
                
                if (games.length > 0) {
                    renderGameList(games, galListContainer);
                } else {
                    console.error('未找到有效的游戏数据');
                }
            } catch (error) {
                console.error('解析游戏数据时出错:', error);
                return;
            }   
        }
    }
    if (!gameDataFound) {
        console.error('未找到游戏数据块');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGameList);
} else {
    initGameList();
}
