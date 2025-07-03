<template>
    <div class="recipe-page">

        <!-- 左サイドバー -->
        <aside class="sidebar">
            <form @submit.prevent="searchRecipes">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                type="text"
                v-model="searchKeyword"
                placeholder="料理名・材料で検索"
                >
            </div>
            <button type="submit">検索</button>
            </form>
        </aside>

        <!-- メイン：レシピ一覧 -->
        <section class="recipe-list">
            <div class="recipe-grid">
            <div
                v-for="recipe in recipes"
                :key="recipe.id"
                class="recipe-card"
            >
                <div class="no-image">No Image</div>
                <div class="recipe-title">{{ recipe.title }}</div>
                <div class="recipe-genre">{{ recipe.genre }}</div>

            </div>
            </div>

            <!-- ページネーション -->
            <div class="pagination">
            <button
                v-if="currentPage > 1"
                @click="goToPage(currentPage - 1)"
                class="pagination-btn"
            >
                前へ
            </button>

            <span
                v-for="page in totalPages"
                :key="page"
                :class="{ active: page === currentPage }"
                @click="goToPage(page)"
                class="pagination-number"
            >
                {{ page }}
            </span>

            <button
                v-if="currentPage < totalPages"
                @click="goToPage(currentPage + 1)"
                class="pagination-btn"
            >
                次へ
            </button>
            </div>
        </section>
        </div>
    </template>

    <script setup>

    import { ref, onMounted, watch } from 'vue'
    import { useRoute, useRouter, useHead } from '#app'

    definePageMeta({
        layout: 'guest'
    })
    useHead({
        link: [
        {
            rel: 'stylesheet',
            href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
        },
        ]
    })

    // データ定義
    const searchKeyword = ref('')
    const currentPage = ref(1)
    const totalPages = ref(1)
    const isAuthenticated = true // 仮にログインしていると仮定
    const recipes = ref([
        { id: 1, title: 'テストレシピ1', genre: 'ジャンル', likes: 24, saved: true },
        { id: 2, title: 'テストレシピ2', genre: 'ジャンル' },
        { id: 3, title: 'テストレシピ3', genre: 'ジャンル' },
        { id: 4, title: 'テストレシピ4', genre: 'ジャンル' },
        { id: 5, title: 'テストレシピ5', genre: 'ジャンル' },
        { id: 6, title: 'テストレシピ6', genre: 'ジャンル' }
    ])

    const route = useRoute()
    const router = useRouter()

    onMounted(() => {
        searchKeyword.value = route.query.keyword || ''
        currentPage.value = parseInt(route.query.page) || 1
        fetchRecipes()
    })

    const searchRecipes = () => {
        currentPage.value = 1
        updateUrl()
        fetchRecipes()
    }

    const goToPage = (page) => {
        currentPage.value = page
        updateUrl()
        fetchRecipes()
    }

    const updateUrl = () => {
        const query = {}
        if (searchKeyword.value) query.keyword = searchKeyword.value
        if (currentPage.value > 1) query.page = currentPage.value
        router.push({ path: '/user', query })
    }

    const fetchRecipes = async () => {
        try {
        console.log('検索:', searchKeyword.value, 'ページ:', currentPage.value)
        // 実際のAPI接続時に書き換えてください
        } catch (error) {
        console.error('レシピ取得エラー:', error)
        }
    }

    watch(() => route.query, (newQuery) => {
        searchKeyword.value = newQuery.keyword || ''
        currentPage.value = parseInt(newQuery.page) || 1
        fetchRecipes()
    })
    </script>

    <style scoped>
    @import "@/assets/css/common.css";

    .recipe-page {
    display: flex;
    padding: 20px;
    gap: 30px;
    max-width: 1400px;
    margin: 0 auto;
}

.sidebar {
    width: 300px;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    height: fit-content;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sidebar h2 {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 15px;
}

.sidebar label {
    font-size: 14px;
    font-weight: bold;
    display: block;
    margin-top: 20px;
    margin-bottom: 8px;
}

.search-wrapper {
    position: relative;
    width: 100%;
}

.search-wrapper i.fa-solid {
    position: absolute;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    color: #e6e5e5;
    pointer-events: none;
}

.search-wrapper input[type="text"] {
    width: 100%;
    padding: 10px 10px 10px 40px;
    font-size: 16px;
    border: 1px solid #adadad;
    border-radius: 6px;
    box-sizing: border-box;
}

.sidebar input,
.sidebar select {
    width: 100%;
    padding: 12px;
    font-size: 14px;
    border: 1px solid #999;
    border-radius: 4px;
    box-sizing: border-box;
}

.search-wrapper input::placeholder {
    color: #ddd;
    opacity: 1;
}

.sidebar button {
    width: 100%;
    background-color: #ddd;
    border: none;
    color: #000;
    padding: 10px;
    font-weight: bold;
    border-radius: 8px;
    margin-top: 20px;
    cursor: pointer;
}

.sidebar button:hover {
    background-color: #e6e5e5;
}

.recipe-list {
    flex: 1;
    min-height: 300px;
}

.recipe-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

@media (max-width: 1024px) {
    .recipe-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .recipe-grid {
        grid-template-columns: 1fr;
    }
}

.recipe-card {
    width: 100%;
    height: 400px;
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 10px;
    box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.1);
    text-align: center;
    background: white;
    box-sizing: border-box;
}

.no-image {
    width: 100%;
    height: 300px;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 14px;
    border-radius: 6px;
}

.recipe-card img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 6px;
}

.recipe-title {
    margin-top: 10px;
    font-weight: bold;
    color: #333;
}

.recipe-genre {
    color: #555;
    margin-bottom: 5px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination-btn {
    padding: 8px 16px;
    background-color: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.pagination-btn:hover {
    background-color: #e9e9e9;
}

.pagination-number {
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
}

.pagination-number:hover {
    background-color: #f0f0f0;
}

.pagination-number.active {
    background-color: #ff770053;
    color: white;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .recipe-page {
        flex-direction: column;
        padding: 15px;
    }

    .sidebar {
        width: 100%;
        order: 2;
    }

    .recipe-list {
        order: 1;
    }

    .recipe-grid {
        grid-template-columns: 1fr;
    }
}
    </style>


