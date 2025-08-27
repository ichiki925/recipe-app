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
      <button @click="goToCreate" class="create-button">＋ 新規レシピ作成</button>
    </aside>


    <!-- メイン：レシピ一覧 -->
    <section class="recipe-list">
      <div v-if="loading" class="loading">
        レシピを読み込み中...
      </div>

      <div v-else-if="recipes.length === 0" class="no-recipes">
        レシピが見つかりませんでした。
      </div>

      <div v-else class="recipe-grid">
        <div
          v-for="recipe in recipes"
          :key="recipe.id"
          class="recipe-card"
          @click="goToRecipeDetail(recipe.id)"
        >
          <div class="recipe-image">
            <img
              :src="recipe.image_full_url || '/images/no-image.png'"
              :alt="recipe.title"
            />
          </div>


          <div class="recipe-title">{{ recipe.title }}</div>

          <div class="recipe-stats">
            <div class="like-display">
              <i class="far fa-heart heart-icon"></i>
              <span class="like-count">{{ recipe.likes_count || 0 }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ページネーション -->
      <div v-if="totalPages > 1" class="pagination">
        <button
          v-if="currentPage > 1"
          @click="goToPage(currentPage - 1)"
          class="pagination-btn"
        >
          ＜
        </button>
        
        <span 
          v-for="page in displayPages" 
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
          ＞
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})
import { ref, onMounted, watch, computed } from 'vue'
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
const recipes = ref([])
const loading = ref(false)
const error = ref('')

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

// ページネーション表示用
const displayPages = computed(() => {
  const pages = []
  const maxDisplay = 5
  const half = Math.floor(maxDisplay / 2)
  let start = Math.max(1, currentPage.value - half)
  let end = Math.min(totalPages.value, start + maxDisplay - 1)
  if (end - start + 1 < maxDisplay) {
    start = Math.max(1, end - maxDisplay + 1)
  }
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// レシピ詳細画面へ遷移
const goToRecipeDetail = (recipeId) => {
  router.push(`/admin/recipes/show/${recipeId}`)
}

// 新規作成画面へ遷移
const goToCreate = () => {
  router.push('/admin/recipes/create')
}

// 削除フラグをチェックする関数
const checkDeleteFlag = () => {
  if (typeof localStorage !== 'undefined') {
    const recipeDeleted = localStorage.getItem('recipeDeleted')
    const deletedRecipeId = localStorage.getItem('deletedRecipeId')
    if (recipeDeleted === 'true') {
      // フラグをクリア
      localStorage.removeItem('recipeDeleted')
      localStorage.removeItem('deletedRecipeId')
      setTimeout(fetchRecipes, 500)
    }
  }
}

// 【追加】更新フラグをチェックする関数
const checkUpdateFlag = () => {
  if (typeof localStorage !== 'undefined') {
    const recipeUpdated = localStorage.getItem('recipeUpdated')
    const updatedRecipeId = localStorage.getItem('updatedRecipeId')
    if (recipeUpdated === 'true') {
      // フラグをクリア
      localStorage.removeItem('recipeUpdated')
      localStorage.removeItem('updatedRecipeId')
      setTimeout(fetchRecipes, 500)
    }
  }
}

// 初期化
onMounted(() => {
  searchKeyword.value = route.query.keyword || ''
  currentPage.value = parseInt(route.query.page) || 1
  checkDeleteFlag()
  checkUpdateFlag()
  fetchRecipes()
})

if (typeof window !== 'undefined') {
  window.addEventListener('focus', () => {
    if (route.path === '/admin/recipes') {
      checkDeleteFlag()
      checkUpdateFlag()
    }
  })
}

// 検索実行
const searchRecipes = () => {
  currentPage.value = 1
  updateUrl()
  fetchRecipes()
}

// ページ移動
const goToPage = (page) => {
  currentPage.value = page
  updateUrl()
  fetchRecipes()
}

// URL更新
const updateUrl = () => {
  try {
    const query = {}
    if (searchKeyword.value) query.keyword = searchKeyword.value
    if (currentPage.value > 1) query.page = currentPage.value
    router.push({ path: '/admin/recipes', query })
  } catch (error) {
    console.error('URL更新エラー:', error)
  }
}

// レシピデータ取得
const fetchRecipes = async () => {
  loading.value = true
  error.value   = ''
  try {
    const { $auth } = useNuxtApp()
    if (!$auth?.currentUser) throw new Error('認証が必要です')
    const token = await $auth.currentUser.getIdToken()

    const params = new URLSearchParams()
    if (searchKeyword.value) params.append('keyword', searchKeyword.value)
    if (currentPage.value > 1) params.append('page', String(currentPage.value))

    // ← ここだけ base を runtimeConfig から取る
    const url = `${config.public.apiBase}/api/admin/recipes${params.toString() ? '?' + params.toString() : ''}`

    const res = await fetch(url, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`)

    const data = await res.json()
    // data.data 内の各要素に image_full_url が入ってくる想定（AdminRecipeResource で付与済み）
    recipes.value     = Array.isArray(data.data) ? data.data : []
    currentPage.value = data.current_page || 1
    totalPages.value  = data.last_page   || 1
  } catch (e) {
    console.error('❌ レシピ取得エラー:', e)
    error.value = 'レシピの取得に失敗しました。'
    recipes.value = []
    currentPage.value = 1
    totalPages.value = 1
  } finally {
    loading.value = false
  }
}




// URLクエリ変更の監視（修正版）
watch(() => route.query, (newQuery) => {
  try {
    searchKeyword.value = newQuery.keyword || ''
    currentPage.value = parseInt(newQuery.page) || 1
    fetchRecipes()
  } catch (error) {
    console.error('クエリ監視エラー:', error)
  }
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

.loading,
.no-recipes {
    text-align: center;
    padding: 40px;
    color: #666;
    font-size: 16px;
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
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.recipe-card:hover {
    transform: translateY(-2px);
    box-shadow: 2px 4px 12px rgba(0, 0, 0, 0.15);
}

.recipe-image {
    width: 100%;
    height: 300px;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
    background-color: #f0f0f0;
}

.recipe-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image-fallback {
    width: 100%;
    height: 100%;
    background-color: #f0f0f0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #999;
    border-radius: 6px;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.no-image-text {
    font-size: 14px;
    font-weight: 500;
    text-align: center;
}

.recipe-title {
    margin-top: 10px;
    font-weight: bold;
    color: #333;
    font-size: 16px;
}

.recipe-stats {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 8px;
    font-size: 12px;
}

.like-display {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #333;
}

.heart-icon {
    font-size: 16px;
    color: #dc3545;
}

.like-count {
    font-size: 12px;
    color: #333;
    font-family: cursive;
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
    background-color: #ffb300c7;
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