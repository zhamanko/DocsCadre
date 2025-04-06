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

<body class="flex flex-row h-screen bg-gray-600 dark:text-white">

    <?php include './../../components/header.php'; ?>

    <main class="w-full flex flex-col justify-center items-center">
        <div class="bg-gray-700 w-full max-w-md rounded-md shadow-xl py-8">
            <h2 class="text-2xl text-center">Додати шаблон</h2>
            <form id="addDocsForm" class="flex flex-col gap-4 p-6">
                <div>
                    <input type="file" id="file" class="file:h-full file:p-2 file:bg-gray-800 w-full bg-gray-600 text-white rounded-md">
                </div>
                <div>
                    <input type="text" id="type" placeholder="Тип шаблону" class="w-full bg-gray-600 text-white p-2 rounded-md">
                </div>
                <div>
                    <?php
                    $categories = include './../../php/getCategory.php';
                    ?>

                    <select name="category" id="category" class="w-full bg-gray-600 text-white p-2 rounded-md">
                        <option value="">Категорія документу</option>
                        <?php
                        foreach ($categories as $category) {
                            echo '<option value="' . htmlspecialchars($category['category']) . '">' . htmlspecialchars($category['category']) . '</option>';
                        }
                        ?>
                    </select>

                </div>
                <button class="w-full bg-gray-600 hover:bg-gray-500 py-2 rounded transition">Додати</button>
            </form>
        </div>
    </main>

    <div id="message" class="fixed bottom-4 right-1/2 transform translate-x-1/2 bg-gray-700 p-4 rounded-md shadow-xl text-white opacity-0 hidden">
        <h3 class="text-lg text-center">Повідомлення</h3>
        <p id="messageText" class="mt-2 text-center">Тут буде ваше повідомлення</p>
    </div>

    <script>
        let btnAddDocs = document.getElementById('addDocs');
        let btnTemplates = document.getElementById('templates');
        let btnCheckTemplates = document.getElementById('checkDocs');

        btnAddDocs.setAttribute('href', 'javascript:void(0);');
        btnAddDocs.classList.add('opacity-50', 'cursor-not-allowed');

        btnCheckTemplates.setAttribute('href', './../checkDocs/');
        btnTemplates.setAttribute('href', './../Template/');

        let inpType = document.getElementById('type');
        let inpCategory = document.getElementById('category');


        document.getElementById('file').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const fileFullName = file.name.split('.').slice(0, -1).join('.');
                const [type, category] = fileFullName.split('_');

                if (type === undefined || category === undefined) {
                    inpType.value = '';
                    inpCategory.value = '';
                    inpCategory.innerHTML = '<option value="">Категорія документу</option>';
                    showMessage('Неправильна назва файлу! Очікуйте на шаблон у форматі "тип_категорія"');
                    return;
                }

                inpType.value = type;

                let categoryExists = false;
                const options = inpCategory.querySelectorAll('option');
                options.forEach(option => {
                    if (option.value === category) {
                        categoryExists = true;
                    }
                });

                if (categoryExists) {
                    inpCategory.value = category;
                    showMessage('Файл обрано!');
                } else {
                    inpCategory.value = '';
                    showMessage('Категорія не знайдена у списку!');
                }
            } else {
                inpType.value = '';
                inpCategory.value = '';
                inpCategory.innerHTML = '<option value="">Категорія документу</option>';
            }
        });


        document.getElementById('addDocsForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const file = document.getElementById('file').files[0];
            const type = inpType.value;
            const category = inpCategory.value;

            if (!file || !type || !category) {
                showMessage('Заповніть всі поля!');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);
            formData.append('category', category);

            fetch('./../../php/addTemplayte.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message);
                    } else {
                        showMessage(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Сталася помилка!');
                });
        });

        function showMessage(message) {
            const messageElement = document.getElementById('message');
            const messageText = document.getElementById('messageText');

            messageText.textContent = message;

            messageElement.classList.remove('hidden', 'animate-slide-in', 'animate-slide-out');

            messageElement.classList.remove('opacity-0');
            messageElement.classList.add('animate-slide-in');

            setTimeout(() => {
                messageElement.classList.remove('animate-slide-in');
                messageElement.classList.add('animate-slide-out');

                setTimeout(() => {
                    messageElement.classList.add('hidden');
                }, 500);
            }, 3000);
        }
    </script>
</body>

</html>