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
<html lang="uk" class="dark">

<head>
    <meta charset="UTF-8">
    <title>Мій сайт</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
    <style>
        @keyframes slideIn {
            0% {
                transform: translateY(100%);
                opacity: 0;
            }

            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            0% {
                transform: translateY(0);
                opacity: 1;
            }

            100% {
                transform: translateY(100%);
                opacity: 0;
            }
        }

        .animate-slide-in {
            animation: slideIn 0.5s ease-out forwards;
        }

        .animate-slide-out {
            animation: slideOut 0.5s ease-in forwards;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body class="flex flex-row min-h-screen bg-gray-600 dark:text-white">

    <?php include '../../components/header.php' ?>

    <div class="flex flex-col gap-4 p-8">
        <h1 class="text-2xl text-center">Редактор документів DOCX</h1>
        <form class="flex flex-col gap-4" method="post" enctype="multipart/form-data">
            <input class="file:h-full file:p-2 file:bg-gray-800 border-1 border-gray-300 w-full bg-gray-600 text-white rounded-md" type="file" name="document" accept=".docx" required>
            <button type="submit" class="text-center w-full bg-gray-700 hover:bg-gray-500 py-2 rounded transition">Завантажити</button>
        </form>
        <div class="flex flex-row gap-4 ">
            <?php if (!empty($_SESSION['preview_content'])): ?>
                <div class="border border-gray-300 p-4 rounded-md w-1/2">
                    <h2 class="text-2xl">Попередній перегляд:</h2>
                    <div id="previewContent">
                        <?= preg_replace('/\[\[(.*?)\]\]/', '<mark data-key="[[\1]]">\1</mark>', htmlspecialchars($_SESSION['preview_content'])) ?>
                    </div>
                </div>

                <form class="flex-1 bg-gray-700 px-8 rounded-md shadow-xl py-5 space-y-4" method="post" id="editForm">
                    <h2 class="text-2xl text-center">Редагувати значення:</h2>
                    <?php foreach ($_SESSION['keys'] as $key):
                        $keyWithoutBrackets = preg_replace('/^\[\[(.*?)\]\]$/', '$1', $key);
                        $isDateField = strpos($keyWithoutBrackets, 'DATE') !== false;
                        $isIDField = $keyWithoutBrackets == 'ID';
                    ?>
                        <div class="">
                            <label><?= htmlspecialchars($key) ?>:</label>
                            <?php if ($isDateField): ?>
                                <input type="date" name="keys[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($keyWithoutBrackets) ?>" oninput="updatePreview('<?= htmlspecialchars($key) ?>', this.value)" class="w-full bg-gray-600 text-white p-2 rounded-md date:text-white">
                            <?php elseif ($isIDField): ?>
                                <input type="number" name="keys[<?= htmlspecialchars($key) ?>]" value="" oninput="updatePreview('<?= htmlspecialchars($key) ?>', this.value)" class="w-full bg-gray-600 text-white p-2 rounded-md">
                            <?php else: ?>
                                <input type="text" name="keys[<?= htmlspecialchars($key) ?>]" value="" placeholder="<?= htmlspecialchars($keyWithoutBrackets) ?>" oninput="updatePreview('<?= htmlspecialchars($key) ?>', this.value)" class="w-full bg-gray-600 text-white p-2 rounded-md">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="save" class="text-center w-full bg-gray-600 hover:bg-gray-500 py-2 rounded transition">Зберегти зміни</button>
                </form>
        </div>
    </div>


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