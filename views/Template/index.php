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

    <main class="w-full flex flex-col p-2">
        <div class="w-full bg-gray-700 rounded-md shadow-xl py-8 px-6">
            <h2 class="text-2xl text-center">Шаблон</h2>
            <div class="flex flex-row gap-4 bg-gray-800 p-4 rounded-md shadow-xl mt-4">
                <div class="w-34">
                    <?php $types = include './../../php/getType.php'; ?>
                    <select name="type" id="filterType" class="w-full bg-gray-600 hover:bg-gray-500 text-white rounded-md p-2 shadow-xl">
                        <option value="">Типи</option>
                        <?php foreach ($types as $type) : ?>
                            <option value="<?= $type['type'] ?>"><?= $type['type'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1">
                    <input type="text" id="filterSearch" name="search" placeholder="Пошук" class="w-full bg-gray-600 hover:bg-gray-500 text-white rounded-md p-2 shadow-xl">
                </div>
                <div class="w-54">
                    <?php $categories = include './../../php/getCategory.php'; ?>
                    <select name="category" id="filterCategory" class="w-full bg-gray-600 hover:bg-gray-500 text-white rounded-md p-2 shadow-xl">
                        <option value="">Категорія</option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?= $category['category'] ?>"><?= $category['category'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div id="listTemplates" class="flex flex-col gap-4 bg-gray-800 p-4 rounded-md shadow-xl mt-4">
            </div>
        </div>
    </main>

    <div id="message" class="fixed bottom-4 right-1/2 transform translate-x-1/2 bg-gray-700 p-4 rounded-md shadow-xl text-white opacity-0 hidden">
        <h3 class="text-lg text-center">Повідомлення</h3>
        <p id="messageText" class="mt-2 text-center">Тут буде ваше повідомлення</p>
    </div>

    <script>
        let btnAddDocs = document.getElementById('addDocs');
        let btnTemplates = document.getElementById('templates');

        btnAddDocs.setAttribute('href', './../addDocs');
        btnTemplates.classList.add('opacity-50', 'cursor-not-allowed');

        const inpType = document.getElementById('filterType');
        const inpCategory = document.getElementById('filterCategory');
        const inpSearch = document.getElementById('filterSearch');

        [inpType, inpCategory, inpSearch].forEach(el => {
            el.addEventListener('input', () => {
                apiSearch(inpType.value, inpCategory.value, inpSearch.value);
            });
        });

        apiSearch();

        function apiSearch(type = "", category = "", search = "") {
            const url = new URL('./../../php/searchTemplate.php', window.location.origin);
            const params = {
                type,
                category,
                search
            };
            Object.keys(params).forEach(key => {
                if (params[key]) url.searchParams.append(key, params[key]);
            });

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const listTemplates = document.getElementById('listTemplates');
                    listTemplates.innerHTML = ""; // Очищення списку перед новим виводом

                    if (data.length > 0) {
                        data.forEach(template => {
                            const link = document.createElement('a');
                            link.href = `./../editDocs?path=${template.path}`;
                            link.classList.add('w-full', 'bg-gray-600', 'hover:bg-gray-500', 'text-center', 'text-white', 'rounded-md', 'py-4', 'shadow-xl');
                            link.textContent = template.type + " - " + template.category;
                            listTemplates.appendChild(link);
                        });
                    } else {
                        const message = document.createElement('p');
                        message.textContent = 'Шаблони не знайдено.';
                        message.classList.add('text-white');
                        listTemplates.appendChild(message);
                    }
                })
                .catch(error => {
                    console.error('Помилка:', error);
                });
        }


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