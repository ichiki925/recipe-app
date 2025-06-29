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

<style scoped>
@import "@/assets/css/common.css";

.recipe-page {
    display: flex;
    padding: 20px;
    gap: 30px;
    /* サイドバーとメインコンテンツの間隔 */
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
    border: 1px solid #ccc;
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
    background-color: #eee;
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

.recipe-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 10px;
}

.like-button {
    display: flex;
    align-items: center;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
    transform: translateY(-5px);
}

.like-button:hover {
    background-color: #f8f9fa;
}

/* .heart-icon {
    vertical-align: middle;
    margin-right: 4px;
    fill: #dc3545;
    transform: translateY(-3px);
} */

.heart-icon-filled {
    color: #dc3545;
    font-size: 16px;
}

.heart-icon-outline {
    color: #333;
    font-size: 16px;
}

.like-count {
    font-size: 12px;
    color: #666;
    transform: translateY(-1.5px);
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