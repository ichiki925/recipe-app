<template>
    <div class="recipe-page">
    
        <!-- ヘッド内の読み込みは script setup 内で行うので Head タグは削除しました -->
    
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
    
    <style>
    @import "@/assets/css/common.css";
    @import "@/assets/css/user/index.css";
    </style>


