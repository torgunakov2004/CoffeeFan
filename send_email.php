<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true); // Используйте true для включения исключений
try {
    // Настройки сервера
    $mail->isSMTP();                                            // Установите использование SMTP
    $mail->Host       = 'smtp.gmail.com';                     // Укажите основной SMTP-сервер
    $mail->SMTPAuth   = true;                                   // Включите аутентификацию SMTP
    $mail->Username   = 'torgunakov.anton.04@gmail.com';      // Ваш логин от почты
    $mail->Password   = 'ghcd ogft dzdb jmrm';                // Ваш пароль от приложения
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // Включите шифрование TLS
    $mail->Port       = 587;                                   // Укажите порт TCP для подключения

    // Настройка получателя и отправителя
    $mail->setFrom('torgunakov.anton.04@gmail.com', 'Anton Torgunakov');
    $mail->addAddress('Ivanov4ic@gmail.com', 'Recipient Name'); // Замените на адрес получателя

    // Добавление содержимого письма
    $mail->isHTML(true);                                  // Установите формат письма в HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    // Добавление файлов (если нужно)
    // $mail->addAttachment('/path/to/file.pdf');         // Добавьте вложение

    // Отправка письма
    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>