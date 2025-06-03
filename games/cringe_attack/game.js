const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const scoreElement = document.getElementById('score');
const highScoreElement = document.getElementById('highScore');
const startMessageDiv = document.getElementById('startMessage');
const gameButton = document.getElementById('gameButton');
const levelUpMessageDiv = document.getElementById('levelUpMessage');
const secretMessageOverlay = document.getElementById('secretMessageOverlay');
const secretMessageTextElement = document.getElementById('secretMessageText');
const continueGameButton = document.getElementById('continueGameButton');
const milestoneScoreDisplayElement = document.getElementById('milestoneScoreDisplay');

const touchLeftButton = document.getElementById('touchLeft');
const touchRightButton = document.getElementById('touchRight');
const touchShootButton = document.getElementById('touchShoot');

const playerVideoElement = document.getElementById('playerVideo');
const shieldVideoElement = document.getElementById('shieldVideo');

let score = 0;
let highScore = parseInt(localStorage.getItem('coffeeFanHighScoreV4')) || 0;
let gameRunning = false;
let player, bullets, enemies, powerUps;
let baseEnemySpeed, currentEnemySpeed, enemySpawnInterval, bulletSpeed, baseShootCooldown, currentShootCooldown;
let lastEnemySpawnTime = 0;
let lastPowerUpSpawnTime = 0;
let powerUpSpawnInterval = 12000;
let keys = {};
let canShoot = true;
let nextSecretScoreThreshold = 500;
const scoreThresholdIncrement = 500;
let lastLevelUpScore = 0;
const levelUpIncrement = 250;
let resourcesLoaded = false;
let gamePausedForSecret = false;
let currentSecretIndex = 0;
let animationFrameId = null;

const secrets = [
    "Сегодня, 19 мая, в нашем колледже прозвенел последний звонок. После торжественной линейки мы с группой отправились отмечать – пили, ели, гуляли. Было весело и круто! Даже искупались (правда, только я и двое моих одногруппников, ха-ха). Я рад, что всё так прошло – это был отличный день.",
    "Позже мы решили зайти в ресторан-бар, ещё раз поесть и выпить. Когда мы вышли, я закурил и увидел красивую дорогую машину. В этот момент меня накрыли мысли: смогу ли я, работая «на стабильность», достичь такого?  Я понимаю, что сравниваю себя с ним, но суть не в этом. Мне грустно от мысли, что буду просто работать «на стабильность», получать среднюю зарплату, а хочется большего – стать руководителем или создать что-то своё, что принесёт и пользу, и финансовую независимость.",
    "Я люблю строить, изобретать, изучать новое – я творческий человек. Мне нравится разбираться в чём-то, видеть результаты своих трудов. Но есть опасения: а вдруг я не найду, чем заняться? А если найду – вдруг не получится? Мне 20 лет, я молод, но знаю, что многие в этом возрасте начинают работать «где придётся» и потом так и остаются на нелюбимом месте. Я не хочу так.",
    "Все 4 года помогал родителям – строил, ремонтировал. Но теперь пора идти своим путём и обеспечить их родителей – ведь у них здоровье уже не то",
    "А ещё у меня есть одна фишка: если я отношусь к человеку как к родному, мне не жалко потратить на него больше денег, чем нужно. Например, разделить счёт в кафе не пополам, а заплатить за него, или просто что-то купить. Ха-ха, вот такой я.",
    "Это была последня часть мыслей. Спасибо за игру!"
];

const enemyImageSources = [
    'img/coffee_bean-плохой-кофе.png', 
    'img/coffee_bean-сумашедший.png', 
    'img/coffee_bean-печаль.png', 
    'img/coffee_bean-счастливый.png', 
    'img/coffee_bean-убегает-радуется.png', 
    'img/coffee_bean-ухмылка.png', 
    'img/coffee_bean-обнимашки.png'
];
let loadedEnemyImages = [];

function preloadImages(sources) {
    return Promise.all(sources.map(src => {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => { console.error(`Failed to load image: ${src}`); resolve(null); };
            img.src = src;
        });
    }));
}

function initVideos() {
    const videoPromises = [];
    if (playerVideoElement) {
        videoPromises.push(new Promise((resolve, reject) => {
            playerVideoElement.oncanplaythrough = resolve;
            playerVideoElement.onerror = () => reject(new Error('Failed to load player video'));
            if (playerVideoElement.readyState >= 3) resolve();
        }));
    } else {  videoPromises.push(Promise.resolve()); }
    if (shieldVideoElement) {
        videoPromises.push(new Promise((resolve, reject) => {
            shieldVideoElement.oncanplaythrough = resolve;
            shieldVideoElement.onerror = () => reject(new Error('Failed to load shield video'));
            if (shieldVideoElement.readyState >= 3) resolve();
        }));
    } else { videoPromises.push(Promise.resolve()); }
    return Promise.all(videoPromises);
}

function resizeCanvas() {
    const aspectRatio = 9 / 16;
    let newWidth = Math.min(window.innerWidth * 0.95, 450);
    let newHeight = newWidth / aspectRatio;
    if (newHeight > window.innerHeight * 0.80) { 
        newHeight = window.innerHeight * 0.80; 
        newWidth = newHeight * aspectRatio; 
    }
    canvas.width = Math.floor(newWidth);
    canvas.height = Math.floor(newHeight);
    if(player) {
      player.width = canvas.width * 0.14; player.height = player.width;
      player.x = Math.max(0, Math.min(player.x, canvas.width - player.width));
      player.y = canvas.height - (player.height + 10);
    }
}

function initGameProperties() {
    const playerSize = canvas.width * 0.14;
    player = { x: canvas.width / 2 - playerSize / 2, y: canvas.height - (playerSize + 10), width: playerSize, height: playerSize, speed: canvas.width * 0.012, shield: false, shieldTime: 0, rapidFire: false, rapidFireTime: 0 };
    bullets = []; enemies = []; powerUps = [];
    baseEnemySpeed = canvas.height * 0.001; currentEnemySpeed = baseEnemySpeed;
    enemySpawnInterval = 2500; bulletSpeed = canvas.height * 0.01;
    baseShootCooldown = 400; currentShootCooldown = baseShootCooldown;
    score = 0; scoreElement.textContent = score; highScoreElement.textContent = highScore;
    canShoot = true;
    lastLevelUpScore = 0;
}

function drawPlayer() {
    if (playerVideoElement && playerVideoElement.readyState >= 3 && player) {
        ctx.drawImage(playerVideoElement, player.x, player.y, player.width, player.height);
        if (player.shield && shieldVideoElement && shieldVideoElement.readyState >= 3) {
            const shieldSize = player.width * 1.4;
            ctx.drawImage(shieldVideoElement, player.x - (shieldSize - player.width) / 2, player.y - (shieldSize - player.height) / 2, shieldSize, shieldSize);
        }
    } else if (player) { ctx.fillStyle = '#C99E71'; ctx.fillRect(player.x, player.y, player.width, player.height); }
}

function drawBullet(bullet) {
    ctx.font = `${bullet.height}px Arial`; ctx.fillStyle = bullet.color;
    ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillText(bullet.char, bullet.x + bullet.width / 2, bullet.y + bullet.height / 2);
}

function drawEnemy(enemy) {
    if (enemy.img && enemy.img.complete && enemy.img.naturalWidth !== 0) {
        ctx.shadowOffsetX = 2; ctx.shadowOffsetY = 2; ctx.shadowBlur = 4; ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
        ctx.drawImage(enemy.img, enemy.x, enemy.y, enemy.width, enemy.height);
        ctx.shadowOffsetX = 0; ctx.shadowOffsetY = 0; ctx.shadowBlur = 0; ctx.shadowColor = 'rgba(0,0,0,0)';
    } else { ctx.fillStyle = '#A52A2A'; ctx.fillRect(enemy.x, enemy.y, enemy.width, enemy.height); }
}

function drawPowerUp(powerUp) {
    if (powerUp.type === 'rapidFire') {
        ctx.font = `${powerUp.height * 0.8}px Arial`; ctx.fillStyle = 'yellow';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText("🍵", powerUp.x + powerUp.width / 2, powerUp.y + powerUp.height / 2);
    } else if (powerUp.type === 'shield') {
        if (shieldVideoElement && shieldVideoElement.readyState >= 3) {
            ctx.drawImage(shieldVideoElement, powerUp.x, powerUp.y, powerUp.width, powerUp.height);
        } else { ctx.fillStyle = 'rgba(173, 216, 230, 0.8)'; ctx.fillRect(powerUp.x, powerUp.y, powerUp.width, powerUp.height); }
    }
}

function update(timestamp) {
    updateBullets();
    updateEnemies(timestamp);
    updatePowerUps(timestamp);
    checkCollisions();
    handleInput();
    handleShooting();
    updatePlayerEffects(timestamp);
    checkLevelUp();
    if (score >= nextSecretScoreThreshold && currentSecretIndex < secrets.length && secretMessageOverlay.style.display !== 'flex') {
        showSecretMessage();
    }
}

function render() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!player) return;
    drawPlayer();
    bullets.forEach(drawBullet);
    enemies.forEach(drawEnemy);
    powerUps.forEach(drawPowerUp);
}

function gameLoop(timestamp) {
    if (gamePausedForSecret) {
        animationFrameId = requestAnimationFrame(gameLoop);
        return;
    }
    if (!gameRunning) {
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
        return;
    }
    update(timestamp);
    render();
    animationFrameId = requestAnimationFrame(gameLoop);
}

function updateBullets() { for (let i = bullets.length - 1; i >= 0; i--) { bullets[i].y -= bulletSpeed; if (bullets[i].y + bullets[i].height < 0) bullets.splice(i, 1); } }

function getRandomEnemyImage() {
    if (loadedEnemyImages.length === 0) return null;
    const validImages = loadedEnemyImages.filter(img => img && img.complete && img.naturalWidth !== 0);
    if (validImages.length === 0) return null;
    return validImages[Math.floor(Math.random() * validImages.length)];
}

function spawnEnemy(timestamp) {
    if (timestamp - lastEnemySpawnTime > enemySpawnInterval) {
        lastEnemySpawnTime = timestamp;
        const size = Math.max(25, canvas.width * (0.08 + Math.random() * 0.06));
        const x = Math.random() * (canvas.width - size);
        const randomImg = getRandomEnemyImage();
        if (randomImg) {
            const imgName = randomImg.src.substring(randomImg.src.lastIndexOf('/') + 1);
            const isStrongerEnemy = ['coffee_bean-плохой-кофе.png', 'coffee_bean-сумашедший.png', 'coffee_bean-печаль.png'].includes(imgName);
            enemies.push({
                x: x, y: 0 - size, width: size, height: size,
                speed: currentEnemySpeed + Math.random() * (currentEnemySpeed * 0.2),
                img: randomImg,
                health: isStrongerEnemy ? 2 : 1
            });
        }
    }
}

function updateEnemies(timestamp) {
    spawnEnemy(timestamp);
    for (let i = enemies.length - 1; i >= 0; i--) {
        enemies[i].y += enemies[i].speed;
        if (enemies[i].y > canvas.height) {
            gameOver();
            return;
        }
    }
}

function spawnPowerUp(timestamp) {
    if (timestamp - lastPowerUpSpawnTime > powerUpSpawnInterval && Math.random() < 0.15) {
        lastPowerUpSpawnTime = timestamp;
        const size = Math.max(20, canvas.width * 0.07);
        const x = Math.random() * (canvas.width - size);
        const type = Math.random() < 0.5 ? 'shield' : 'rapidFire';
        powerUps.push({ x: x, y: 0 - size, width: size, height: size, type: type, speed: baseEnemySpeed * 0.7 });
    }
}

function updatePowerUps(timestamp){
    spawnPowerUp(timestamp);
    for(let i = powerUps.length -1; i >= 0; i--){
        powerUps[i].y += powerUps[i].speed;
        if(powerUps[i].y > canvas.height) powerUps.splice(i,1);
    }
}

function updatePlayerEffects(timestamp){
    const now = timestamp;
    if(player.shield && now > player.shieldTime) player.shield = false;
    if(player.rapidFire && now > player.rapidFireTime) {
        player.rapidFire = false;
        currentShootCooldown = baseShootCooldown;
    }
}

function checkCollisions() {
    if (!player) return;
    for (let i = bullets.length - 1; i >= 0; i--) {
        for (let j = enemies.length - 1; j >= 0; j--) {
            if (!bullets[i] || !enemies[j]) continue;
            if (bullets[i].x - bullets[i].width/2 < enemies[j].x + enemies[j].width &&
                bullets[i].x + bullets[i].width/2 > enemies[j].x &&
                bullets[i].y - bullets[i].height/2 < enemies[j].y + enemies[j].height &&
                bullets[i].y + bullets[i].height/2 > enemies[j].y) {
                enemies[j].health--;
                bullets.splice(i, 1);
                if(enemies[j].health <= 0){
                    enemies.splice(j, 1);
                    score += 10;
                } else {
                    score += 3;
                }
                scoreElement.textContent = score;
                break;
            }
        }
    }
    for (let i = enemies.length - 1; i >= 0; i--) {
        if (!enemies[i] || !player) continue;
        if (enemies[i].x < player.x + player.width &&
            enemies[i].x + enemies[i].width > player.x &&
            enemies[i].y < player.y + player.height &&
            enemies[i].y + enemies[i].height > player.y) {
            if(player.shield){
                enemies.splice(i,1);
                player.shield = false;
                score += 5;
                scoreElement.textContent = score;
            } else {
                gameOver();
                return;
            }
        }
    }
    for(let i = powerUps.length - 1; i>=0; i--){
        if (!powerUps[i] || !player) continue;
        if(powerUps[i].x < player.x + player.width &&
           powerUps[i].x + powerUps[i].width > player.x &&
           powerUps[i].y < player.y + player.height &&
           powerUps[i].y + powerUps[i].height > player.y){
            activatePowerUp(powerUps[i].type);
            powerUps.splice(i,1);
        }
    }
}

function activatePowerUp(type){
    const nowTime = performance.now();
    if(type === 'shield'){
        player.shield = true;
        player.shieldTime = nowTime + 7000;
    } else if (type === 'rapidFire'){
        player.rapidFire = true;
        player.rapidFireTime = nowTime + 7000;
        currentShootCooldown = baseShootCooldown / 2.5;
    }
}

window.addEventListener('keydown', function (e) {
    keys[e.code] = true;
    if(e.code === 'Space' && !gameRunning && !gamePausedForSecret &&
       (startMessageDiv.style.display === 'flex' || (secretMessageOverlay.style.display === 'none' && document.getElementById('restartGameButtonInternal')) ) ) {
        e.preventDefault();
        const btn = document.getElementById('gameButton') || document.getElementById('restartGameButtonInternal');
        if(btn && !btn.disabled) btn.click();
    } else if (e.code === 'Enter' && gamePausedForSecret && secretMessageOverlay.style.display === 'flex' && continueGameButton) {
        e.preventDefault();
        continueGameButton.click();
    }
});
window.addEventListener('keyup', function (e) { keys[e.code] = false; });

function handleInput() {
    if (!player) return;
    if (keys['ArrowLeft'] || keys['KeyA']) player.x -= player.speed;
    if (keys['ArrowRight'] || keys['KeyD']) player.x += player.speed;
    player.x = Math.max(0, Math.min(player.x, canvas.width - player.width));
}

function handleShooting() {
    if (!keys['Space'] || !canShoot || !player) return;
    const bulletSize = canvas.width * 0.05;
    bullets.push({
        x: player.x + player.width / 2 - bulletSize / 2,
        y: player.y - bulletSize/2,
        width: bulletSize, height: bulletSize, speed: bulletSpeed,
        char: '☕', angle: 0, color: '#FFF8DC' });
    canShoot = false;
    setTimeout(() => { canShoot = true; }, player.rapidFire ? currentShootCooldown : baseShootCooldown);
}

function startGameFlow() {
    initGameProperties();
    startMessageDiv.style.display = 'none';
    secretMessageOverlay.style.display = 'none';
    levelUpMessageDiv.style.display = 'none';
    gamePausedForSecret = false;
    currentSecretIndex = 0;
    nextSecretScoreThreshold = scoreThresholdIncrement;
    const currentTime = performance.now();
    lastEnemySpawnTime = currentTime;
    lastPowerUpSpawnTime = currentTime;
    if (playerVideoElement && playerVideoElement.paused) playerVideoElement.play().catch(e => console.warn("Ошибка play player video:", e));
    if (shieldVideoElement && shieldVideoElement.paused) shieldVideoElement.play().catch(e => console.warn("Ошибка play shield video:", e));
    gameRunning = true;
    if (!animationFrameId) {
        animationFrameId = requestAnimationFrame(gameLoop);
    }
}

function gameOver() {
    if (!gameRunning && startMessageDiv.style.display === 'flex' && document.getElementById('restartGameButtonInternal')) {
        return;
    }
    gameRunning = false;
    gamePausedForSecret = false;
    if (secretMessageOverlay.style.display === 'flex') {
        secretMessageOverlay.style.display = 'none';
        if (window.continueGameButtonHandler) {
            continueGameButton.removeEventListener('click', window.continueGameButtonHandler);
            window.continueGameButtonHandler = null;
        }
    }
    if (score > highScore) {
        highScore = score;
        localStorage.setItem('coffeeFanHighScoreV4', highScore);
        highScoreElement.textContent = highScore;
    }
    startMessageDiv.innerHTML = `<h2>Игра окончена!</h2><p>Ваш счет: ${score}</p><button id="restartGameButtonInternal" class="restartGameButtonInternal">Играть снова</button>`;
    startMessageDiv.style.display = 'flex';
    const restartButton = document.getElementById('restartGameButtonInternal');
    if (restartButton) {
        restartButton.addEventListener('click', handleStartButtonClick);
    }
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }
}

function checkLevelUp(){
    if (score > 0 && score >= lastLevelUpScore + levelUpIncrement) {
        lastLevelUpScore += levelUpIncrement;
        currentEnemySpeed = Math.min(canvas.height * 0.004, currentEnemySpeed + canvas.height * 0.00015);
        enemySpawnInterval = Math.max(350, enemySpawnInterval - 60);
        levelUpMessageDiv.style.display = 'flex';
        setTimeout(() => {levelUpMessageDiv.style.display = 'none';}, 1800);
    }
}

function showSecretMessage() {
    if (secretMessageOverlay.style.display === 'flex' || currentSecretIndex >= secrets.length) return;
    gamePausedForSecret = true;
    if (milestoneScoreDisplayElement) milestoneScoreDisplayElement.textContent = nextSecretScoreThreshold;
    if (secretMessageTextElement) secretMessageTextElement.textContent = secrets[currentSecretIndex];
    secretMessageOverlay.style.display = 'flex';
    if (window.continueGameButtonHandler) {
        continueGameButton.removeEventListener('click', window.continueGameButtonHandler);
    }
    window.continueGameButtonHandler = function() {
        secretMessageOverlay.style.display = 'none';
        currentSecretIndex++;
        nextSecretScoreThreshold += scoreThresholdIncrement;
        const resumeTime = performance.now();
        lastEnemySpawnTime = resumeTime;
        lastPowerUpSpawnTime = resumeTime;
        gamePausedForSecret = false;
        if (gameRunning && !animationFrameId) {
             animationFrameId = requestAnimationFrame(gameLoop);
        }
        continueGameButton.removeEventListener('click', window.continueGameButtonHandler);
        window.continueGameButtonHandler = null;
    };
    continueGameButton.addEventListener('click', window.continueGameButtonHandler);
    setTimeout(() => continueGameButton.focus(), 0);
}

function handleStartButtonClick() {
    const buttonToHandle = document.getElementById('gameButton') || document.getElementById('restartGameButtonInternal');
    if (buttonToHandle && !buttonToHandle.disabled) {
        buttonToHandle.disabled = true;
        buttonToHandle.textContent = "Загрузка...";
        if (!resourcesLoaded) {
            Promise.all([preloadImages(enemyImageSources), initVideos()])
                .then(([loadedImgs, videoResults]) => {
                    loadedEnemyImages = loadedImgs.filter(img => img !== null);
                    resourcesLoaded = true;
                    startGameFlow();
                })
                .catch(err => {
                    console.error("Ошибка загрузки ресурсов:", err);
                    if (startMessageDiv && buttonToHandle) {
                        startMessageDiv.innerHTML = `<h2>Ошибка загрузки!</h2><p>Не удалось загрузить ресурсы игры. Проверьте пути к файлам и интернет-соединение.</p><button id="restartGameButtonInternal" class="restartGameButtonInternal">Попробовать снова</button>`;
                        startMessageDiv.style.display = 'flex';
                        const newRestartButton = document.getElementById('restartGameButtonInternal');
                        if (newRestartButton) {
                           newRestartButton.addEventListener('click', handleStartButtonClick);
                           newRestartButton.disabled = false;
                        }
                    }
                });
        } else {
            startGameFlow();
        }
    }
}

if (touchLeftButton && touchRightButton && touchShootButton) {
    touchLeftButton.addEventListener('touchstart', (e) => { e.preventDefault(); keys['ArrowLeft'] = true; });
    touchLeftButton.addEventListener('touchend', (e) => { e.preventDefault(); keys['ArrowLeft'] = false; });
    touchRightButton.addEventListener('touchstart', (e) => { e.preventDefault(); keys['ArrowRight'] = true; });
    touchRightButton.addEventListener('touchend', (e) => { e.preventDefault(); keys['ArrowRight'] = false; });
    touchShootButton.addEventListener('touchstart', (e) => {
        e.preventDefault();
        keys['Space'] = true;
        if(gameRunning && !gamePausedForSecret && canShoot && player) {
            handleShooting();
        }
    });
    touchShootButton.addEventListener('touchend', (e) => { e.preventDefault(); keys['Space'] = false; });
}

document.addEventListener('DOMContentLoaded', () => {
    const gameBtn = document.getElementById('gameButton');
    if (gameBtn) {
        gameBtn.addEventListener('click', handleStartButtonClick);
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();
    if (highScoreElement) highScoreElement.textContent = highScore;
});