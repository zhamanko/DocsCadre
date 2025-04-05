<?php
require_once './../../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $tmpPath = $file['tmp_name'];

    $_SESSION['original_docx'] = file_get_contents($tmpPath);

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

if (isset($_POST['save']) && isset($_SESSION['original_docx'])) {
    $tempFile = tempnam(sys_get_temp_dir(), 'docx');
    file_put_contents($tempFile, $_SESSION['original_docx']);

    $zip = new ZipArchive();
    if ($zip->open($tempFile) === true) {
        $xml = $zip->getFromName('word/document.xml');

        foreach ($_POST['keys'] as $oldKey => $newValue) {
            $oldKeyWithoutBrackets = trim($oldKey, '[]');

            if (strpos($oldKeyWithoutBrackets, 'DATE') !== false && !empty($newValue)) {
                $date = DateTime::createFromFormat('Y-m-d', $newValue);
                $newValue = $date ? $date->format('d.m.Y') : $newValue;
            }

            $xml = preg_replace('/\[\[' . preg_quote($oldKeyWithoutBrackets, '/') . '\]\]/', $newValue, $xml);
        }

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

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
                // Перевірка, чи це плейсхолдер для дати
                $isDateField = strpos($keyWithoutBrackets, 'DATE') !== false;
                // Перевірка, чи це ключ для ID
                $isIDField = $keyWithoutBrackets == 'ID';
            ?>
                <div class="key-input">
                    <label><?= htmlspecialchars($key) ?>:</label>
                    <?php if ($isDateField): ?>
                        <!-- Поле для вибору дати (формат YYYY-MM-DD) -->
                        <input type="date" name="keys[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($keyWithoutBrackets) ?>" oninput="updatePreview('<?= htmlspecialchars($key) ?>', this.value)">
                    <?php elseif ($isIDField): ?>
                        <!-- Поле для числа (ID) -->
                        <input type="number" name="keys[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($keyWithoutBrackets) ?>" oninput="updatePreview('<?= htmlspecialchars($key) ?>', this.value)">
                    <?php else: ?>
                        <!-- Поле для текстового введення -->
                        <input type="text" name="keys[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($keyWithoutBrackets) ?>" oninput="updatePreview('<?= htmlspecialchars($key) ?>', this.value)">
                    <?php endif; ?>
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

            document.querySelectorAll('input[type="text"]').forEach(input => {
                input.addEventListener('input', function(e) {
                    const value = e.target.value;
                    if (/^\d{2}(\.\d{0,2})?(\.\d{0,4})?$/.test(value)) {
                        e.target.value = value.replace(/^(\d{2})(\d{0,2})(\d{0,4})?$/, '$1.$2.$3');
                    }
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>
