<?php
$pathToGamesHub = '../../games_hub.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0, minimum-scale=1.0">
    <title>CoffeeFan - Атака Кринж-Кофе!</title>
    <style>
        body { margin: 0; background-color: #14110E; display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; overflow: hidden; font-family: 'Urbanist', sans-serif; color: #fff; touch-action: manipulation; -webkit-user-select: none; -ms-user-select: none; user-select: none; }
        #gameContainer { text-align: center; position: relative; }
        #gameCanvas { border: 3px solid #C99E71; background-color: #2a2623; background-image: url('img/footer_coffee_beans_fall.gif'); background-size: cover; display: block; margin: 0 auto; max-width: 100%; max-height: 85vh; }
        .ui-overlay { position: absolute; top: 0; left: 0; width: 100%; padding: 10px; box-sizing: border-box; display: flex; justify-content: space-between; align-items: center; z-index: 10; pointer-events: none;}
        #scoreBoard, #highScoreBoard { font-size: clamp(16px, 4vw, 22px); color: #C99E71; text-shadow: 1px 1px 2px #000; pointer-events: auto;}
        #highScoreBoard { text-align: right;}
        #startMessage, #levelUpMessage, #secretMessageOverlay { display: none; flex-direction: column; justify-content: center; align-items: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; background-color: rgba(20, 17, 14, 0.95); padding: 20px; border-radius: 12px; border: 1px solid #C99E71; z-index: 20; box-shadow: 0 0 15px rgba(201, 158, 113, 0.3); width: 80%; max-width: 350px; max-height: 80vh; overflow-y: auto; }
        #startMessage h2, #levelUpMessage h2, #secretMessageOverlay h2 { margin-top: 0; color: #C99E71; font-family: 'Righteous', cursive; font-size: clamp(18px, 5vw, 24px); margin-bottom: 10px;}
        #startMessage p, #levelUpMessage p, #secretMessageOverlay p { margin-bottom: 15px; font-size: clamp(13px, 3.5vw, 15px); line-height: 1.5; text-align: left; white-space: pre-wrap; }
        #gameButton, .restartGameButtonInternal, #continueGameButton { background-color: #C99E71; color: #14110E; border: none; padding: 10px 20px; border-radius: 6px; font-size: clamp(15px, 4vw, 17px); cursor: pointer; font-weight: bold; transition: background-color 0.3s ease; margin-top: 15px;}
        #gameButton:hover, .restartGameButtonInternal:hover, #continueGameButton:hover { background-color: #bd864b; }
        .touch-controls { display: none; position: fixed; bottom: 10px; width: 100%; justify-content: space-around; z-index: 15;}
        .touch-controls button { background-color: rgba(201, 158, 113, 0.6); color: #14110E; border: 1px solid rgba(0,0,0,0.2); border-radius: 10px; padding: 12px 18px; font-size: 22px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
        .hidden-videos { display: none; }
        .back-to-games-hub {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 100;
            background-color: rgba(201, 158, 113, 0.7);
            color: #14110E;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            font-size: clamp(12px, 3vw, 14px);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s ease;
        }
        .back-to-games-hub:hover {
            background-color: #C99E71;
        }
        .back-to-games-hub .material-icons-outlined {
            font-size: clamp(16px, 4vw, 18px);
        }

        @media (max-width: 700px) or (pointer: coarse) { .touch-controls { display: flex; } #startMessage p:nth-of-type(2) {display: none;} }
        #secretMessageOverlay p { max-height: 50vh; overflow-y: auto; padding-right: 10px; text-align: justify; }
        #secretMessageOverlay::-webkit-scrollbar, #secretMessageOverlay p::-webkit-scrollbar { width: 6px; }
        #secretMessageOverlay::-webkit-scrollbar-track, #secretMessageOverlay p::-webkit-scrollbar-track { background: #332e2a; border-radius:3px;}
        #secretMessageOverlay::-webkit-scrollbar-thumb, #secretMessageOverlay p::-webkit-scrollbar-thumb { background-color: #C99E71; border-radius: 3px;}
    </style>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <a href="<?php echo htmlspecialchars($pathToGamesHub); ?>" class="back-to-games-hub">
        <span class="material-icons-outlined">arrow_back_ios</span> К выбору игр
    </a>
    <div id="gameContainer">
        <div class="ui-overlay">
            <div id="scoreBoard">Очки: <span id="score">0</span></div>
            <div id="highScoreBoard">Рекорд: <span id="highScore">0</span></div>
        </div>
        <canvas id="gameCanvas"></canvas>
        <div id="startMessage" style="display:flex;">
            <h2>Атака Кринж-Кофе!</h2>
            <p>Защити качественный кофе!</p>
            <p>Управление: Стрелки ← → или A/D, Пробел - стрелять.</p>
            <button id="gameButton">Начать игру!</button>
        </div>
        <div id="levelUpMessage" style="display:none;">
            <h2>Новый Уровень!</h2>
            <p>Враги стали немного быстрее!</p>
        </div>
        <div id="secretMessageOverlay" style="display:none;">
            <h2>Поздравляем! <span id="milestoneScoreDisplay">0</span> очков!</h2>
            <p id="secretMessageText"></p>
            <button id="continueGameButton">Продолжить</button>
        </div>
    </div>
    <div class="touch-controls">
        <button id="touchLeft">←</button>
        <button id="touchShoot">☕</button>
        <button id="touchRight">→</button>
    </div>
    <div class="hidden-videos">
        <video id="playerVideo" src="img/footer_coffee_steam_light.mp4" loop muted playsinline></video>
        <video id="shieldVideo" src="img/Щит.mp4" loop muted playsinline></video>
    </div>
    <script src="game.js?v=<?php echo time(); ?>"></script> 
</body>
</html>