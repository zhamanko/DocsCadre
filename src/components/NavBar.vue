<template>
    <div class="bg-white dark:bg-gray-800 w-60 h-screen p-4 shadow">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">DocsCadre</h1>
            <button @click="toggleTheme"
                class="text-sm px-2 py-1 rounded bg-gray-200 text-white dark:text-black dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition flex items-center justify-center">
                <img v-if="theme === 'dark'" src="@/assets/icons/sun.svg" alt="Світла тема" class="w-5 h-5" />
                <img v-else src="@/assets/icons/moon.svg" alt="Темна тема" class="w-5 h-5" />
            </button>
        </div>
        <div>
            <nav>
                <router-link to="docs">Докементи</router-link>
            </nav>
        </div>
    </div>
</template>

<script>
export default {
    name: 'NavBar',
    data() {
        return {
            theme: 'light',
        }
    },
    mounted() {
        // Авто-встановлення теми з localStorage
        const savedTheme = localStorage.theme || 'light'
        this.theme = savedTheme
        document.documentElement.classList.toggle('dark', this.theme === 'dark')
    },
    methods: {
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark'
            document.documentElement.classList.toggle('dark', this.theme === 'dark')
            localStorage.theme = this.theme
        },
    },
}
</script>