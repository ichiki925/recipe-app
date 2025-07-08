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
          @click="goToRecipeDetail(recipe.id)"
        >
          <div class="no-image">No Image</div>
          <div class="recipe-title">{{ recipe.title }}</div>
          <div class="recipe-genre">{{ recipe.genre }}</div>
          <div class="recipe-stats">
            <button 
              @click.stop="toggleFavorite(recipe)"
              class="like-button"
              :class="{ liked: recipe.isFavorited }"
              :title="recipe.isFavorited ? 'お気に入りから削除' : 'お気に入りに追加'"
            >
              <!-- お気に入り済みの場合は塗りつぶし -->
              <i 
                v-if="recipe.isFavorited"
                class="fas fa-heart heart-icon-filled"
              ></i>
              <!-- 未お気に入りの場合は枠線のハート -->
              <i 
                v-else
                class="far fa-heart heart-icon-outline"
              ></i>
              <span class="like-count">{{ recipe.likes }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ページネーション -->
      <div class="pagination">
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
// ページメタデータ（middlewareを削除）
definePageMeta({
  // middleware を削除して、onMounted で認証チェック
})

// FontAwesome CSS読み込み - ヘッダーに移動
useHead({
  link: [
    {
      rel: 'stylesheet',
      href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
      crossorigin: 'anonymous'
    },
  ]
})

// 認証関連
const { getCurrentUser, user, isLoggedIn, waitForAuth } = useAuth()

// データ定義
const searchKeyword = ref('')
const currentPage = ref(1)
const totalPages = ref(2)

// お気に入り状態を含むレシピデータ
const recipes = ref([
  { id: 1, title: 'チキンカレー', genre: '和食', likes: 24, isFavorited: true },
  { id: 2, title: 'パスタボロネーゼ', genre: 'イタリアン', likes: 15, isFavorited: false },
  { id: 3, title: '麻婆豆腐', genre: '中華', likes: 8, isFavorited: false },
  { id: 4, title: 'ハンバーグ', genre: '洋食', likes: 32, isFavorited: false },
  { id: 5, title: '親子丼', genre: '和食', likes: 5, isFavorited: false },
  { id: 6, title: 'グラタン', genre: '洋食', likes: 19, isFavorited: true }
])

const route = useRoute()
const router = useRouter()

// お気に入り状態管理用のグローバルストア
const favoriteStore = useState('favorites', () => new Set([1, 6])) // 初期値として1と6をお気に入り

// コンポーネント初期化
onMounted(async () => {
  console.log('🔍 /user ページの認証チェック開始...')
  
  // Firebase認証の状態確立を待機
  const currentUser = await waitForAuth()
  
  console.log('👤 認証チェック結果:', currentUser ? currentUser.email : 'null')
  console.log('👤 useAuthのuser:', user.value)
  
  if (!currentUser) {
    console.log('⚠️ 認証失敗 - ログインページにリダイレクト')
    await navigateTo('/auth/login')
    return
  }
  
  console.log('✅ 認証成功:', currentUser.email, 'レシピ一覧ページを表示')
  
  // お気に入り状態を同期
  syncFavoriteStatus()
  
  // 初期データ読み込み
  searchKeyword.value = route.query.keyword || ''
  currentPage.value = parseInt(route.query.page) || 1
  fetchRecipes()
})

// お気に入り状態を同期
const syncFavoriteStatus = () => {
  recipes.value.forEach(recipe => {
    recipe.isFavorited = favoriteStore.value.has(recipe.id)
  })
  console.log('🔄 お気に入り状態を同期しました')
}

// 詳細ページへの遷移
const goToRecipeDetail = (recipeId) => {
  console.log('📖 レシピ詳細ページへ遷移:', recipeId)
  navigateTo(`/user/show/${recipeId}`)
}

// お気に入りボタンの切り替え
const toggleFavorite = async (recipe) => {
  console.log('🖱️ ハートボタンがクリックされました:', recipe.title)
  
  if (!user.value) {
    console.log('⚠️ ログインが必要です')
    return
  }

  // 元の状態を保存（エラー時のロールバック用）
  const originalState = recipe.isFavorited
  const originalLikes = recipe.likes
  
  // UI を先に更新（楽観的更新）
  recipe.isFavorited = !recipe.isFavorited
  
  if (recipe.isFavorited) {
    favoriteStore.value.add(recipe.id)
    recipe.likes++
    console.log(`💖 ${user.value.email} がレシピ${recipe.id}「${recipe.title}」をお気に入りに追加`)
  } else {
    favoriteStore.value.delete(recipe.id)
    recipe.likes = Math.max(0, recipe.likes - 1)
    console.log(`💔 ${user.value.email} がレシピ${recipe.id}「${recipe.title}」をお気に入りから削除`)
  }

  // Laravel API へのリクエスト
  try {
    const { getIdToken } = useAuth()
    const token = await getIdToken()
    const config = useRuntimeConfig()

    const response = await $fetch(`/recipes/${recipe.id}/toggle-like`, {
      baseURL: config.public.apiBaseUrl,
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })

    console.log('✅ API応答:', response)
    
    // サーバーからの正確な値で更新
    recipe.isFavorited = response.is_liked
    recipe.likes = response.likes_count
    
    // お気に入りストアも更新
    if (response.is_liked) {
      favoriteStore.value.add(recipe.id)
    } else {
      favoriteStore.value.delete(recipe.id)
    }

  } catch (error) {
    console.error('❌ いいね更新エラー:', error)
    
    // エラー時は状態を元に戻す（ロールバック）
    recipe.isFavorited = originalState
    recipe.likes = originalLikes
    
    if (originalState) {
      favoriteStore.value.add(recipe.id)
    } else {
      favoriteStore.value.delete(recipe.id)
    }
    
    // ユーザーにエラーを通知
    alert('いいねの更新に失敗しました。もう一度お試しください。')
  }
}

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
    console.log('🔍 検索:', searchKeyword.value, 'ページ:', currentPage.value)

    const config = useRuntimeConfig()
    let headers = {}
    
    // ログイン済みの場合はトークンを追加
    if (user.value) {
      const { getIdToken } = useAuth()
      const token = await getIdToken()
      headers.Authorization = `Bearer ${token}`
    }

    const response = await $fetch('/recipes', {
      baseURL: config.public.apiBaseUrl,
      headers,
      query: {
        keyword: searchKeyword.value,
        page: currentPage.value,
        sort: 'latest'
      }
    })

    console.log('📦 API応答:', response)
    
    // レシピデータを更新
    recipes.value = response.data.map(recipe => ({
      id: recipe.id,
      title: recipe.title,
      genre: recipe.genre,
      likes: recipe.likes_count,
      isFavorited: recipe.is_liked || false,
      image_url: recipe.image_url,
      admin: recipe.admin
    }))
    
    // ページネーション情報更新
    currentPage.value = response.current_page
    totalPages.value = response.last_page
    
    // お気に入りストアを同期
    favoriteStore.value.clear()
    recipes.value.forEach(recipe => {
      if (recipe.isFavorited) {
        favoriteStore.value.add(recipe.id)
      }
    })

  } catch (error) {
    console.error('❌ レシピ取得エラー:', error)
    
    // エラー時はモックデータを使用
    console.log('📋 モックデータを使用します')
    syncFavoriteStatus()
  }
}

// URLクエリの監視
watch(() => route.query, (newQuery) => {
  searchKeyword.value = newQuery.keyword || ''
  currentPage.value = parseInt(newQuery.page) || 1
  fetchRecipes()
})

// お気に入りストアの変更を監視
watch(favoriteStore, () => {
  syncFavoriteStatus()
}, { deep: true })

const fetchUserFavorites = async () => {
  try {
    const { getIdToken } = useAuth()
    const token = await getIdToken()
    const config = useRuntimeConfig()

    const response = await $fetch('/user/liked-recipes', {
      baseURL: config.public.apiBaseUrl,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      query: {
        page: currentPage.value
      }
    })

    console.log('💖 お気に入りレシピ取得:', response)
    
    return response.data.map(recipe => ({
      id: recipe.id,
      title: recipe.title,
      genre: recipe.genre,
      likes: recipe.likes_count,
      isFavorited: true,
      image_url: recipe.image_url,
      admin: recipe.admin
    }))

  } catch (error) {
    console.error('❌ お気に入り取得エラー:', error)
    return []
  }
}

</script>



<style scoped>
@import "@/assets/css/common.css";

.recipe-page {
  padding: 20px;
  gap: 30px;
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
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
  border-radius: 4px;
  transition: all 0.2s ease;
  transform: translateY(-5px);
  pointer-events: auto !important;
  padding: 8px 12px;
}

.like-button:hover {
  background-color: #f8f9fa;
  transform: translateY(-7px);
}

.like-button:active {
  transform: translateY(-3px);
}

.heart-icon-filled,
.heart-icon-outline {
  /* アイコンのポインターイベントを無効化してボタンのクリックを優先 */
  pointer-events: none;
  font-size: 16px;
}

.heart-icon-filled {
  color: #dc3545;
  animation: heartBeat 0.3s ease;
}

.heart-icon-outline {
  color: #333;
  transition: color 0.2s ease;
}

.like-button:hover .heart-icon-outline {
  color: #dc3545;
}

/* .heart-icon-outline:hover {
  color: #dc3545;
} */

.like-count {
  font-size: 12px;
  color: #333;
  transform: translateY(-1.5px);
  pointer-events: none;
}

.like-button.liked  {
  color: #dc3545;
  font-weight: 500;
}


@keyframes heartBeat {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
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
  color: #000;
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