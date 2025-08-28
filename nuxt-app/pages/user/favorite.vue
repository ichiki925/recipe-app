<template>
  <div class="recipe-page">

    <!-- 左サイドバー -->
    <RecipeSearchSection
      user-type="user"
      :initial-keyword="searchKeyword"
      placeholder="お気に入りレシピを検索"
      @search="handleSearch"
      @clear-search="handleClearSearch"
    />

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
          <div class="recipe-image">
            <img
              :src="recipe.image_full_url || '/images/no-image.png'"
              :alt="recipe.title"
              class="recipe-img"
              loading="lazy"
              decoding="async"
              @error="e => { e.target.onerror = null; e.target.src = '/images/no-image.png' }"
            />
          </div>
          <div class="recipe-title">{{ recipe.title }}</div>
          <div class="recipe-genre">{{ recipe.genre }}</div>
          <div class="recipe-stats">
            <button
              @click.stop="toggleLike(recipe)"
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
const { user, isLoggedIn, initAuth, getIdToken } = useAuth()

// データ定義
const searchKeyword = ref('')
const currentPage = ref(1)
const recipesPerPage = 6
const isLoading = ref(false)

// お気に入りレシピデータ
const favoriteRecipes = ref([])

const route = useRoute()
const router = useRouter()

// お気に入り状態管理用のグローバルストア
const favoriteStore = useState('favorites', () => new Set())

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

const getImageUrl = (imageUrl) => {
  console.log(`🔍 getImageUrl called with: ${imageUrl}`)
  
  if (!imageUrl) {
    console.log(`🔍 No image URL, returning default`)
    return '/images/no-image.png'
  }

  if (imageUrl.startsWith('/storage/')) {
    const fullUrl = `http://localhost:8000${imageUrl}`
    console.log(`🔍 Converted relative URL to: ${fullUrl}`)
    return fullUrl
  }

  console.log(`🔍 Using original URL: ${imageUrl}`)
  return imageUrl
}



const handleImageError = (event, recipe) => {
  console.log(`❌ 画像読み込みエラー: ${recipe.title}`)
  console.log(`❌ 画像URL: ${recipe.image_url}`)
  console.log(`❌ 処理済みURL: ${getImageUrl(recipe.image_url)}`)

  event.target.onerror = null
  const parent = event.target.parentElement
  event.target.style.display = 'none'

  if (!parent.querySelector('.no-image-fallback')) {
    const placeholder = document.createElement('div')
    placeholder.className = 'no-image-fallback'
    placeholder.innerHTML = '<div class="no-image-text">No Image</div>'
    parent.appendChild(placeholder)
  }
}

// お気に入りレシピをAPIから取得
// お気に入りレシピをAPIから取得
const fetchFavoriteRecipes = async () => {
  if (!user.value) return

  try {
    isLoading.value = true
    const config = useRuntimeConfig()
    const { $auth } = useNuxtApp()
    const token = await $auth.currentUser.getIdToken()

    const response = await $fetch(`${config.public.apiBase}/api/user/liked-recipes`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      query: { keyword: searchKeyword.value || '', page: 1, per_page: 100 }
    })

    // 想定: { data: [...] }
    const items = Array.isArray(response?.data) ? response.data : []
    favoriteRecipes.value = items.map((r) => {
      // ① バックエンドが返す絶対URLを優先
      // ② なければ /storage/... を絶対URLに直す
      // ③ それもなければ null
      const img =
        r.image_full_url ??
        (r.image_url
          ? (String(r.image_url).startsWith('/storage/')
              ? `${config.public.apiBase}${r.image_url}`
              : r.image_url)
          : null)

      return {
        id: r.id,
        title: r.title,
        genre: r.genre,
        likes: r.likes_count ?? 0,
        isFavorited: true,
        image_full_url: img,
        admin: r.admin ?? null,
      }
    })

    // ストア同期
    favoriteStore.value.clear()
    favoriteRecipes.value.forEach((r) => favoriteStore.value.add(r.id))
    favoriteStore.value = new Set(favoriteStore.value)

  } catch (error) {
    console.error('❌ お気に入りレシピ取得エラー:', error)
    favoriteRecipes.value = []
    favoriteStore.value.clear()
    alert('お気に入りレシピの取得に失敗しました。ページを再読み込みしてください。')
  } finally {
    isLoading.value = false
  }
}


// 詳細ページへの遷移
const goToRecipeDetail = (recipeId) => {
  console.log('📖 お気に入りページから詳細ページへ遷移:', recipeId)
  navigateTo(`/user/show/${recipeId}`)
}

// コンポーネント初期化
onMounted(async () => {
  console.log('🔍 お気に入りページの認証チェック開始...')

  try {
    // 🔧 修正：waitForAuthではなく、他のページと同じ方法を使用
    await initAuth()
    console.log('👤 認証チェック結果:', user.value ? user.value.email : 'null')

    if (!user.value) {
      console.log('⚠️ 認証失敗 - ログインページにリダイレクト')
      await navigateTo('/auth/login')
      return
    }

    console.log('✅ 認証成功:', user.value.email, 'お気に入りページを表示')

    // 初期データ読み込み
    searchKeyword.value = route.query.keyword || ''
    currentPage.value = parseInt(route.query.page) || 1

    // お気に入りレシピを取得
    await fetchFavoriteRecipes()

  } catch (error) {
    console.error('❌ 認証処理エラー:', error)
    await navigateTo('/auth/login')
  }
})

// お気に入りから削除する機能（API連携版）
const toggleLike = async (recipe) => {
  if (!user.value) return

  try {
    console.log(`💔 レシピ${recipe.id}「${recipe.title}」をお気に入りから削除中...`)

    // 🔧 楽観的更新: UIから即座に削除
    const recipeElement = document.querySelector(`[data-recipe-id="${recipe.id}"]`)
    if (recipeElement) {
      recipeElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease'
      recipeElement.style.opacity = '0'
      recipeElement.style.transform = 'scale(0.8)'
    }

    // 🔧 楽観的更新: ストアから即座に削除
    favoriteStore.value.delete(recipe.id)

    // 🔧 楽観的更新: リストからも即座に削除
    favoriteRecipes.value = favoriteRecipes.value.filter(r => r.id !== recipe.id)

    // 🔧 追加: ページネーション自動調整
    const remainingRecipes = favoriteRecipes.value.length
    const maxPages = Math.ceil(remainingRecipes / recipesPerPage)
    
    console.log(`📊 削除後の状況:`, {
      remainingRecipes,
      currentPage: currentPage.value,
      maxPages,
      recipesPerPage
    })

    // 現在のページが最大ページを超えている場合、前のページに戻る
    if (currentPage.value > maxPages && maxPages > 0) {
      console.log(`🔄 ページ調整: ${currentPage.value} → ${maxPages}`)
      currentPage.value = maxPages
      updateUrl() // URLも更新
    }
    
    // 全部削除された場合は1ページ目に戻る
    if (remainingRecipes === 0) {
      console.log(`🔄 全削除のため1ページ目に戻る`)
      currentPage.value = 1
      updateUrl()
    }

    const config = useRuntimeConfig()
    const { $auth } = useNuxtApp()
    const token = await $auth.currentUser.getIdToken()


    const response = await $fetch(`${config.public.apiBase}/api/recipes/${recipe.id}/toggle-like`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })

    console.log('✅ お気に入り削除API応答:', response)

    // 🔧 APIが成功した場合の最終確認
    if (response && !response.is_liked) {
      console.log(`💔 レシピ${recipe.id}「${recipe.title}」をお気に入りから削除しました（API確認済み）`)
      
      // 🔧 ストア変更を強制的にトリガー（他のページに通知）
      favoriteStore.value = new Set(favoriteStore.value)
      
    } else {
      // APIレスポンスが期待と異なる場合はロールバック
      throw new Error('APIレスポンスが期待と異なります')
    }

  } catch (error) {
    console.error('❌ お気に入り削除エラー:', error)

    // 🔧 エラー時は楽観的更新をロールバック
    favoriteStore.value.add(recipe.id)
    
    // レシピをリストに戻す
    if (!favoriteRecipes.value.find(r => r.id === recipe.id)) {
      favoriteRecipes.value.push(recipe)
    }

    // アニメーションを元に戻す
    const recipeElement = document.querySelector(`[data-recipe-id="${recipe.id}"]`)
    if (recipeElement) {
      recipeElement.style.opacity = '1'
      recipeElement.style.transform = 'scale(1)'
    }

    alert('お気に入りの削除に失敗しました。もう一度お試しください。')
  }
}


const handleSearch = (keyword) => {
  searchKeyword.value = keyword
  currentPage.value = 1
  updateUrl()
  console.log('🔍 お気に入りレシピ検索:', searchKeyword.value)
  // 検索はクライアントサイドで実行（filteredRecipesが自動更新）
}

const handleClearSearch = () => {
  searchKeyword.value = ''
  currentPage.value = 1
  updateUrl()
  // お気に入りページでは検索はクライアントサイドなのでfetchは不要
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

// お気に入りストアの変更を監視してデータを再取得
watch(favoriteStore, async (newFavorites, oldFavorites) => {
  // 初回実行や同じ参照の場合はスキップ
  if (!oldFavorites || newFavorites === oldFavorites) return

  console.log('🔄 お気に入りページ: ストア変更を検知')
  console.log('新しいストア:', Array.from(newFavorites))
  console.log('古いストア:', Array.from(oldFavorites))

  // サイズが変わった場合のみ再取得
  if (newFavorites.size !== oldFavorites.size) {
    console.log('📊 ストアサイズ変更を検知、データ再取得')
    await fetchFavoriteRecipes()
  } else {
    // サイズが同じでも中身が違う場合があるのでチェック
    const newArray = Array.from(newFavorites).sort()
    const oldArray = Array.from(oldFavorites).sort()

    if (JSON.stringify(newArray) !== JSON.stringify(oldArray)) {
      console.log('📊 ストア内容変更を検知、データ再取得')
      await fetchFavoriteRecipes()
    }
  }
}, { deep: true })
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

.recipe-list {
    flex: 1;
    min-height: 300px;
}

.recipe-image {
    width: 100%;
    height: 300px;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}

.recipe-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 6px;
    transition: transform 0.2s ease;
}

.recipe-card:hover .recipe-img {
    transform: scale(1.05);
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

@media (max-width: 768px) {
    .recipe-page {
      flex-direction: column;
      padding: 15px;
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