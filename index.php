<!DOCTYPE html>
<html lang="uk" class="dark">

<head>
    <meta charset="UTF-8">
    <title>Мій сайт</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
</head>

<body class="flex flex-row h-screen bg-gray-600 dark:text-white">

    <?php include './components/header.php'; ?>

    <main class="w-full flex justify-center items-center ">
        <h2 class="text-3xl">Ласкаво просимо до DocsCadre</h2>
    </main>

</body>

</html>