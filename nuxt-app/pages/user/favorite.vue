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
            placeholder="お気に入りレシピを検索"
          >
        </div>
        <button type="submit">検索</button>
      </form>
    </aside>

    <!-- メイン：お気に入りレシピ一覧 -->
    <section class="recipe-list">
      <h2 class="page-title">
        <i class="fas fa-heart"></i>
        お気に入りレシピ ({{ filteredRecipes.length }}件)
      </h2>

      <!-- レシピが0件の場合のメッセージ -->
      <div v-if="filteredRecipes.length === 0" class="no-recipes">
        <div class="empty-state">
          <i class="far fa-heart empty-heart"></i>
          <h3>お気に入りのレシピがありません</h3>
          <p>レシピ一覧で♡をクリックして、<br>お気に入りに追加してみましょう！</p>
          <NuxtLink to="/user" class="back-to-recipes">
            レシピ一覧に戻る
          </NuxtLink>
        </div>
      </div>

      <div v-else class="recipe-grid">
        <div 
          v-for="recipe in paginatedRecipes" 
          :key="recipe.id" 
          class="recipe-card"
          :data-recipe-id="recipe.id"
          @click="goToRecipeDetail(recipe.id)"
        >
          <div class="no-image">No Image</div>
          <div class="recipe-title">{{ recipe.title }}</div>
          <div class="recipe-genre">{{ recipe.genre }}</div>
          <div class="recipe-stats">
            <button 
              @click.stop="removeFavorite(recipe)"
              class="like-button liked"
              title="お気に入りから削除"
            >
              <i class="fas fa-heart heart-icon-filled"></i>
              <span class="like-count">{{ recipe.likes }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ページネーション -->
      <div class="pagination" v-if="filteredRecipes.length > recipesPerPage">
        <button 
          v-if="currentPage > 1"
          @click="goToPage(currentPage - 1)"
          class="pagination-btn"
        >
          ＜
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
          ＞
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
// ページメタデータ
definePageMeta({
  // layout: 'default' が自動適用
})

useHead({
  link: [
    {
      rel: 'stylesheet',
      href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
    },
  ]
})

// 認証関連
const { getCurrentUser, waitForAuth } = useAuth()
const user = ref(null)

// データ定義
const searchKeyword = ref('')
const currentPage = ref(1)
const recipesPerPage = 6

// 全レシピデータ（本来はAPIから取得）
const allRecipes = ref([
  { id: 1, title: 'チキンカレー', genre: '和食', likes: 24 },
  { id: 2, title: 'パスタボロネーゼ', genre: 'イタリアン', likes: 15 },
  { id: 3, title: '麻婆豆腐', genre: '中華', likes: 8 },
  { id: 4, title: 'ハンバーグ', genre: '洋食', likes: 32 },
  { id: 5, title: '親子丼', genre: '和食', likes: 5 },
  { id: 6, title: 'グラタン', genre: '洋食', likes: 19 }
])

const route = useRoute()
const router = useRouter()

// お気に入り状態管理用のグローバルストア
const favoriteStore = useState('favorites', () => new Set())

// お気に入りレシピのみをフィルタリング
const favoriteRecipes = computed(() => {
  return allRecipes.value.filter(recipe => favoriteStore.value.has(recipe.id))
})

// 検索でフィルタリング
const filteredRecipes = computed(() => {
  if (!searchKeyword.value) {
    return favoriteRecipes.value
  }
  
  const keyword = searchKeyword.value.toLowerCase()
  return favoriteRecipes.value.filter(recipe => 
    recipe.title.toLowerCase().includes(keyword) ||
    recipe.genre.toLowerCase().includes(keyword)
  )
})

// ページネーション計算
const totalPages = computed(() => {
  return Math.ceil(filteredRecipes.value.length / recipesPerPage)
})

const paginatedRecipes = computed(() => {
  const start = (currentPage.value - 1) * recipesPerPage
  const end = start + recipesPerPage
  return filteredRecipes.value.slice(start, end)
})

// 詳細ページへの遷移
const goToRecipeDetail = (recipeId) => {
  console.log('📖 お気に入りページから詳細ページへ遷移:', recipeId)
  navigateTo(`/user/show/${recipeId}`)
}

// コンポーネント初期化
onMounted(async () => {
  console.log('🔍 お気に入りページの認証チェック開始...')
  
  // Firebase認証の状態確立を待機
  const currentUser = await waitForAuth()
  
  console.log('👤 認証チェック結果:', currentUser ? currentUser.email : 'null')
  
  if (!currentUser) {
    console.log('⚠️ 認証失敗 - ログインページにリダイレクト')
    await navigateTo('/auth/login')
    return
  }
  
  console.log('✅ 認証成功:', currentUser.email, 'お気に入りページを表示')
  user.value = currentUser
  
  // 初期データ読み込み
  searchKeyword.value = route.query.keyword || ''
  currentPage.value = parseInt(route.query.page) || 1
  
  console.log('💖 お気に入り件数:', favoriteRecipes.value.length)
})

// お気に入りから削除する機能
const removeFavorite = async (recipe) => {
  try {
    // アニメーション効果
    const recipeElement = document.querySelector(`[data-recipe-id="${recipe.id}"]`)
    if (recipeElement) {
      recipeElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease'
      recipeElement.style.opacity = '0'
      recipeElement.style.transform = 'scale(0.8)'
    }
    
    // 少し遅延してからお気に入りストアから削除
    setTimeout(() => {
      favoriteStore.value.delete(recipe.id)
      console.log(`💔 レシピ${recipe.id}「${recipe.title}」をお気に入りから削除しました`)
      
      // ページが空になった場合は前のページに戻る
      if (paginatedRecipes.value.length === 0 && currentPage.value > 1) {
        currentPage.value = currentPage.value - 1
        updateUrl()
      }
    }, 300)
    
    // 実際のAPIコール（将来実装）
    // await $fetch(`/api/favorites/${recipe.id}`, { 
    //   method: 'DELETE',
    //   body: { userId: user.value.uid }
    // })
    
  } catch (error) {
    console.error('❌ お気に入り削除エラー:', error)
    // エラーの場合は元に戻す処理
    favoriteStore.value.add(recipe.id)
  }
}

const searchRecipes = () => {
  currentPage.value = 1
  updateUrl()
  console.log('🔍 お気に入りレシピ検索:', searchKeyword.value)
}

const goToPage = (page) => {
  currentPage.value = page
  updateUrl()
}

const updateUrl = () => {
  const query = {}
  if (searchKeyword.value) query.keyword = searchKeyword.value
  if (currentPage.value > 1) query.page = currentPage.value
  router.push({ path: '/user/favorite', query })
}

// URLクエリの監視
watch(() => route.query, (newQuery) => {
  searchKeyword.value = newQuery.keyword || ''
  currentPage.value = parseInt(newQuery.page) || 1
})

// 検索キーワード変更時にページをリセット
watch(searchKeyword, () => {
  currentPage.value = 1
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

.page-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.5rem;
  color: #111;
  margin-bottom: 20px;
  font-weight: lighter;
}

.page-title i {
  color: #dc3545;
}

.no-recipes {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.empty-state {
  text-align: center;
  color: #333;
}

.empty-heart {
  font-size: 4rem;
  color: #ddd;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
  color: #333;
}

.empty-state p {
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.back-to-recipes {
  display: inline-block;
  background-color: #dc3545;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  text-decoration: none;
  transition: background-color 0.3s ease;
}

.back-to-recipes:hover {
  background-color: #c82333;
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
    transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.3s ease;
}

.recipe-card:hover {
    transform: translateY(-2px);
    box-shadow: 2px 4px 12px rgba(0, 0, 0, 0.15);
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
    transition: all 0.2s ease;
    transform: translateY(-5px);
}

.like-button:hover {
    background-color: #f8f9fa;
    transform: translateY(-7px);
}

.like-button.liked {
  animation: heartPulse 0.3s ease;
}


.heart-icon-filled {
    color: #dc3545;
    font-size: 16px;
}

.like-count {
  font-size: 12px;
  color: #dc3545;
  font-weight: 500;
  transform: translateY(-1.5px);
}

.like-count {
  font-size: 12px;
  color: #333;
  transform: translateY(-1.5px);
}

/* ハートパルスアニメーション */
@keyframes heartPulse {
  0% { transform: translateY(-5px) scale(1); }
  50% { transform: translateY(-5px) scale(1.1); }
  100% { transform: translateY(-5px) scale(1); }
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
    transition: background-color 0.2s ease;
}

.pagination-btn:hover {
    background-color: #e9e9e9;
}

.pagination-number {
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    transition: background-color 0.2s ease;
    color: #000;
}

.pagination-number:hover {
    background-color: #f0f0f0;
}

.pagination-number.active {
    background-color: #ffb300c7;
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
  color: #333;
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

    .page-title {
      font-size: 1.2rem;
    }
}


</style>