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
          <div class="recipe-stats" v-if="recipe.likes">
            <button 
              @click="removeFavorite(recipe.id)"
              class="like-button liked clickable"
              title="お気に入りから削除"
            >
              <i class="fas fa-heart heart-icon-filled"></i>
              <span class="like-count">{{ recipe.likes }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- レシピが0件の場合のメッセージ -->
      <div v-if="recipes.length === 0" class="no-recipes">
        <p>お気に入りのレシピがありません</p>
      </div>

      <!-- ページネーション -->
      <div class="pagination" v-if="recipes.length > 0">
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
  { id: 1, title: 'お気に入りレシピ1', genre: 'ジャンル', likes: 24, favorited: true },
  { id: 3, title: 'お気に入りレシピ3', genre: 'ジャンル', likes: 15, favorited: true },
  { id: 7, title: 'お気に入りレシピ4', genre: 'ジャンル', likes: 8, favorited: true }
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
  router.push({ path: '/user/favorite', query })
}

const fetchRecipes = async () => {
  try {
    console.log('お気に入りレシピ検索:', searchKeyword.value, 'ページ:', currentPage.value)
    // 実際のAPI接続時に書き換えてください
    // const response = await $fetch('/api/user/favorites', {
    //   query: {
    //     keyword: searchKeyword.value,
    //     page: currentPage.value
    //   }
    // })
    // recipes.value = response.data
    // totalPages.value = response.totalPages
  } catch (error) {
    console.error('お気に入りレシピ取得エラー:', error)
  }
}

// お気に入りから削除する機能
const removeFavorite = async (recipeId) => {
  try {
    // レシピを配列から削除（アニメーション効果付き）
    const recipeElement = document.querySelector(`[data-recipe-id="${recipeId}"]`)
    if (recipeElement) {
      recipeElement.style.transition = 'opacity 0.3s, transform 0.3s'
      recipeElement.style.opacity = '0'
      recipeElement.style.transform = 'scale(0.8)'
    }
    
    // 少し遅延してから実際に削除
    setTimeout(() => {
      recipes.value = recipes.value.filter(recipe => recipe.id !== recipeId)
      console.log(`レシピID ${recipeId} をお気に入りから削除しました`)
    }, 300)
    
    // 実際のAPI呼び出し
    // await $fetch(`/api/user/favorites/${recipeId}`, { 
    //   method: 'DELETE' 
    // })
    
  } catch (error) {
    console.error('お気に入り削除エラー:', error)
    // エラーの場合は元に戻す処理も追加可能
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
@import "@/assets/css/user/favorite.css";

/* お気に入りページ専用のスタイル */
.like-button.clickable {
  cursor: pointer;
  transition: transform 0.2s, opacity 0.2s;
}

.like-button.clickable:hover {
  transform: scale(1.1);
  opacity: 0.8;
}

.no-recipes {
  text-align: center;
  padding: 40px;
  color: #666;
  font-size: 18px;
}

.recipe-card {
  transition: opacity 0.3s, transform 0.3s;
}
</style>