<?php
// profile/handle_chat_message.php
session_start(); 

// 1. Подключаем автозагрузчик Composer
// Файл handle_chat_message.php находится в /profile/
// Папка vendor находится в корне сайта, то есть на один уровень выше.
$autoloadPath = __DIR__ . '/../vendor/autoload.php'; 
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode(['reply' => 'Ошибка сервера: Не найден автозагрузчик Composer.']);
    error_log("Composer autoload.php not found at: " . $autoloadPath);
    exit();
}
require_once $autoloadPath;

// Раскомментируйте, если нужен доступ к вашей БД для чего-либо еще
// require_once '../config/connect.php'; 

// 2. Используем классы из библиотеки Dialogflow
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\ApiCore\ApiException; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $user_message = trim($_POST['message']);
    $bot_response_text = "Извините, произошла ошибка при обработке вашего запроса."; // Ответ по умолчанию

    // 3. Уникальный идентификатор сессии для Dialogflow
    $session_id_string = session_id(); 
    if (empty($session_id_string)) {
        $session_id_string = 'coffee_fan_user_' . uniqid();
        error_log("PHP session_id() was empty, generated temporary: " . $session_id_string);
    }

    // 4. Настройки для Dialogflow API
    $project_id = 'torgunakov-1719050463019'; // Ваш GCP Project ID
    
    // Путь к вашему JSON файлу с учетными данными.
    // Файл handle_chat_message.php в /profile/
    // Файл ключа в /google_cloud_keys/
    // Значит, путь от handle_chat_message.php будет '../google_cloud_keys/dialogflow_credentials.json'
    $credentials_path = __DIR__ . '/../google_cloud_keys/dialogflow_credentials.json'; 

    try {
        if (!file_exists($credentials_path)) {
            throw new Exception("Критическая ошибка: Файл учетных данных Dialogflow не найден. Путь: " . $credentials_path);
        }
        if (!is_readable($credentials_path)) {
            throw new Exception("Критическая ошибка: Файл учетных данных Dialogflow не доступен для чтения. Проверьте права доступа. Путь: " . $credentials_path);
        }

        // 5. Инициализация клиента Dialogflow
        $sessionsClient = new SessionsClient(['credentials' => $credentials_path]);
        $sessionName = $sessionsClient->sessionName($project_id, $session_id_string);

        // 6. Подготовка запроса к Dialogflow
        $textInput = new TextInput();
        $textInput->setText($user_message);
        $textInput->setLanguageCode('ru-RU'); 

        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        // 7. Отправка запроса и получение ответа
        $response = $sessionsClient->detectIntent($sessionName, $queryInput);
        $queryResult = $response->getQueryResult();
        $fulfillmentText = $queryResult->getFulfillmentText();

        if (!empty($fulfillmentText)) {
            $bot_response_text = $fulfillmentText;
        } else {
            $bot_response_text = "Я не совсем уверен, как на это ответить. Можете попробовать спросить по-другому?";
            // error_log("Dialogflow QueryResult (empty fulfillmentText): " . $queryResult->serializeToJsonString());
        }

        $sessionsClient->close();

    } catch (ApiException $e) { 
        error_log("Dialogflow ApiException: " . $e->getMessage() . " | Code: " . $e->getCode() . " | Status: " . $e->getStatus() . " | Raw Response: " . ($e->getBasicMessage() ?? 'N/A'));
        $bot_response_text = "Произошла ошибка при обращении к сервису чат-бота (API). Пожалуйста, попробуйте позже.";
    } catch (Exception $e) { 
        error_log("Dialogflow General Error in handle_chat_message.php: " . $e->getMessage());
        $bot_response_text = "Произошла внутренняя ошибка сервера при обработке вашего запроса. Пожалуйста, попробуйте позже.";
    }

    echo json_encode(['reply' => $bot_response_text]);
    exit();

} else {
    http_response_code(400); 
    echo json_encode(['reply' => 'Ошибка: Некорректный запрос. Отсутствует сообщение.']);
    exit();
}
?>