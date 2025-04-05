<?php
require_once './../../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

session_start();

// Завантаження DOCX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $tmpPath = $file['tmp_name'];

    // Зберігаємо оригінал у сесії
    $_SESSION['original_docx'] = file_get_contents($tmpPath);

    // Читаємо вміст для попереднього перегляду
    $phpWord = IOFactory::load($tmpPath);
    $content = '';

    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                $content .= $element->getText() . "\n";
            }
        }
    }

    preg_match_all('/\[\[(.*?)\]\]/', $content, $matches);
    $_SESSION['keys'] = array_unique($matches[0]);
    $_SESSION['preview_content'] = $content;
}

// Збереження з редагованими значеннями
if (isset($_POST['save']) && isset($_SESSION['original_docx'])) {
    $tempFile = tempnam(sys_get_temp_dir(), 'docx');
    file_put_contents($tempFile, $_SESSION['original_docx']);
    
    $zip = new ZipArchive();
    if ($zip->open($tempFile) === true) {
        // Зчитуємо XML вміст документа
        $xml = $zip->getFromName('word/document.xml');

        // Заміняємо плейсхолдери
        foreach ($_POST['keys'] as $oldKey => $newValue) {
            // Очищаємо ключ без дужок
            $oldKeyWithoutBrackets = trim($oldKey, '[]'); // Видаляємо квадратні дужки по обидва боки

            // Заміна плейсхолдера у документі
            // Застосовуємо правильну екрановану заміну
            $xml = preg_replace('/\[\[' . preg_quote($oldKeyWithoutBrackets, '/') . '\]\]/', $newValue, $xml);
        }

        // Оновлюємо файл в архіві
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        // Відправляємо файл назад на скачування
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="modified.docx"');
        readfile($tempFile);
        unlink($tempFile);
        exit;
    } else {
        echo "Не вдалося відкрити DOCX.";
    }
}


?>

<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <title>Редактор DOCX</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: auto;
            padding: 20px;
        }

        .preview {
            border: 1px solid #ccc;
            padding: 15px;
            margin: 20px 0;
            background: #f9f9f9;
        }

        mark {
            background: #ffd54f;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .key-input {
            margin: 10px 0;
        }

        button {
            padding: 8px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #45a049;
        }
    </style>
</head>

<body>
    <h1>Редактор документів DOCX</h1>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="document" accept=".docx" required>
        <button type="submit">Завантажити</button>
    </form>

    <?php if (!empty($_SESSION['preview_content'])): ?>
        <div class="preview">
            <h2>Попередній перегляд:</h2>
            <div id="previewContent">
                <?= preg_replace('/\[\[(.*?)\]\]/', '<mark data-key="[[\1]]">\1</mark>', htmlspecialchars($_SESSION['preview_content'])) ?>
            </div>
        </div>

        <form method="post" id="editForm">
            <h2>Редагувати значення:</h2>
            <?php foreach ($_SESSION['keys'] as $key):
                $keyWithoutBrackets = preg_replace('/^\[\[(.*?)\]\]$/', '$1', $key);
            ?>
                <div class="key-input">
                    <label><?= htmlspecialchars($key) ?>:</label>
                    <input type="text"
                        name="keys[<?= htmlspecialchars($key) ?>]"
                        value="<?= htmlspecialchars($keyWithoutBrackets) ?>"
                        oninput="updatePreview('<?= htmlspecialchars($key) ?>', this.value)">
                </div>
            <?php endforeach; ?>
            <button type="submit" name="save">Зберегти зміни</button>
        </form>

        <script>
            const originalContent = `<?= preg_replace('/\[\[(.*?)\]\]/', '<mark data-key="[[\1]]">\1</mark>', htmlspecialchars($_SESSION['preview_content'])) ?>`;
            const preview = document.getElementById('previewContent');
            const keyValues = {};
            preview.innerHTML = originalContent;

            function escapeRegex(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            function updatePreview(originalKey, newValue) {
                keyValues[originalKey] = newValue;
                let updated = originalContent;

                for (const [key, val] of Object.entries(keyValues)) {
                    const safeKey = escapeRegex(key);
                    const regex = new RegExp(`<mark data-key="${safeKey}">.*?<\\/mark>`, 'g');
                    updated = updated.replace(regex, `<mark data-key="${key}">${val}</mark>`);
                }

                preview.innerHTML = updated;
            }
        </script>
    <?php endif; ?>
</body>

</html>