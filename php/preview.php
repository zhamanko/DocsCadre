<?php
require_once './../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;

// Шлях до шаблону
$templatePath = './../src/template/67f105df620b8-Наказ_прийняття на роботу основним.docx';

// Дані з форми
$formData = $_GET;

// Зчитування XML з документу
$zip = new ZipArchive;
$placeholders = [];

if ($zip->open($templatePath) === true) {
    $xmlContent = $zip->getFromName('word/document.xml');
    
    // Витягуємо всі плейсхолдери
    preg_match_all('/\[\[\s*(.*?)\s*\]\]/', $xmlContent, $matches);
    $placeholders = array_unique($matches[1]);
    $zip->close();
}

// Використовуємо PhpWord для обробки документа
$template = new TemplateProcessor($templatePath);

// Заміна плейсхолдерів на значення з форми
foreach ($placeholders as $ph) {
    $template->setValue("[[$ph]]", htmlspecialchars($formData[$ph] ?? ''));
}

// Зберегти тимчасовий файл DOCX
$tempDocx = tempnam(sys_get_temp_dir(), 'doc') . '.docx';
$template->saveAs($tempDocx);

// Відкрити як PhpWord документ
$phpWord = IOFactory::load($tempDocx, 'Word2007');

// Вивести HTML
$htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
header('Content-Type: text/html; charset=utf-8');
$htmlWriter->save('php://output');

unlink($tempDocx);
exit;
