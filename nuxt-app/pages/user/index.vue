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
          <div class="recipe-image">
              <img
                  :src="getImageUrl(recipe.image_url)"
                  :alt="recipe.title"
                  @error="handleImageError($event, recipe)"
              />
          </div>
          <div class="recipe-title">{{ recipe.title }}</div>
          <div class="recipe-genre">{{ recipe.genre }}</div>
          <div class="recipe-stats">
            <button
              @click="toggleLike(recipe, $event)"
              class="like-button"
              :class="{ liked: recipe.isFavorited }"
              :title="recipe.isFavorited ? 'お気に入りから削除' : 'お気に入りに追加'"
            >
              <i
                v-if="recipe.isFavorited"
                class="fas fa-heart heart-icon-filled"
              ></i>
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
import { ref, onMounted, watch, nextTick } from 'vue'


// FontAwesome CSS読み込み
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
const { user, isLoggedIn, initAuth } = useAuth()


// データ定義
const searchKeyword = ref('')
const currentPage = ref(1)
const totalPages = ref(1)

// お気に入り状態を含むレシピデータ
const recipes = ref([])

const route = useRoute()
const router = useRouter()

// 画像URL処理関数
const getImageUrl = (imageUrl) => {
    if (!imageUrl) return '/images/no-image.png'

    if (imageUrl.startsWith('/storage/')) {
        return `http://localhost${imageUrl}`
    }

    return imageUrl
}

// 画像エラーハンドリング
const handleImageError = (event, recipe) => {
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

// お気に入り状態管理用のグローバルストア
const favoriteStore = useState('favorites', () => new Set()) // 初期値として1と6をお気に入り

// コンポーネント初期化
onMounted(async () => {

  try {
    await initAuth()

    if (!isLoggedIn.value || !user.value) {
      await navigateTo('/auth/login')
      return
    }

    // URLクエリの設定
    searchKeyword.value = route.query.keyword || ''
    currentPage.value = parseInt(route.query.page) || 1

    // レシピ取得
    await fetchRecipes()

  } catch (error) {
    console.error('認証エラー:', error)
    await navigateTo('/auth/login')
  }
})

// 詳細ページへの遷移
const goToRecipeDetail = (recipeId) => {
  navigateTo(`/user/show/${recipeId}`)
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
    
    // 🔧 重要: fetchRecipes開始時のストア状態を詳細確認
    console.log('💖 fetchRecipes開始時のストア詳細:')
    console.log('💖 - ストア内容:', Array.from(favoriteStore.value))
    console.log('💖 - ストアサイズ:', favoriteStore.value.size)
    console.log('💖 - ストアの型:', typeof favoriteStore.value)
    console.log('💖 - ストアが Set かどうか:', favoriteStore.value instanceof Set)

    const config = useRuntimeConfig()
    let headers = {}

    if (user.value) {
      const { $auth } = useNuxtApp()
      const token = await $auth.currentUser.getIdToken()
      headers.Authorization = `Bearer ${token}`
    }

    const response = await $fetch(`${config.public.apiBase}/api/user/recipes`, {
      method: 'GET',
      headers,
      query: {
        keyword: searchKeyword.value || '',
        page: currentPage.value,
        sort: 'latest'
      }
    })

    console.log('📦 API応答:', response)

    if (!response.data || !Array.isArray(response.data)) {
      console.error('❌ APIレスポンスの形式が不正:', response)
      throw new Error('APIレスポンスの形式が不正です')
    }

    // 🔧 重要: APIデータ処理前にストアを再確認
    console.log('💖 APIデータ処理前のストア:', Array.from(favoriteStore.value))

    // レシピデータを更新（既存のコードのまま）
    recipes.value = response.data.map((recipe, index) => {
      console.log(`📝 Recipe ${index + 1} (ID: ${recipe.id}):`, {
        title: recipe.title,
        is_liked: recipe.is_liked,
        likes_count: recipe.likes_count
      })

      const isLikedFromApi = Boolean(recipe.is_liked)
      const isLikedInStore = favoriteStore.value.has(recipe.id)
      
      console.log(`🔍 レシピ${recipe.id}の状態比較:`, {
        API: isLikedFromApi,
        Store: isLikedInStore,
        Title: recipe.title
      })

      // 🔧 重要: ストア操作の詳細ログ
      if (isLikedFromApi && !isLikedInStore) {
        console.log(`🔄 レシピ${recipe.id}「${recipe.title}」をストアに追加（API優先）`)
        favoriteStore.value.add(recipe.id)
        console.log(`🔄 追加後のストア:`, Array.from(favoriteStore.value))
      } else if (!isLikedFromApi && isLikedInStore) {
        console.log(`🔄 レシピ${recipe.id}「${recipe.title}」をストアから削除（API優先）`)
        favoriteStore.value.delete(recipe.id)
        console.log(`🔄 削除後のストア:`, Array.from(favoriteStore.value))
      }

      return {
        id: recipe.id,
        title: recipe.title,
        genre: recipe.genre,
        likes: recipe.likes_count || 0,
        isFavorited: isLikedFromApi,
        image_url: recipe.image_url,
        admin: recipe.admin
      }
    })

    // ページネーション情報更新
    currentPage.value = response.current_page
    totalPages.value = response.last_page

    await nextTick()
    
    // 🔧 最終確認（詳細版）
    console.log('💖 ===== 最終状態確認 =====')
    console.log('💖 処理後のお気に入りストア:', Array.from(favoriteStore.value))
    console.log('💖 ストアサイズ:', favoriteStore.value.size)
    
    recipes.value.forEach((recipe, index) => {
      const inStore = favoriteStore.value.has(recipe.id)
      console.log(`🔍 最終チェック Recipe ${index + 1}:`, {
        id: recipe.id,
        title: recipe.title,
        isFavorited: recipe.isFavorited,
        shouldShowRedHeart: recipe.isFavorited ? 'YES' : 'NO',
        inStore: inStore,
        consistent: recipe.isFavorited === inStore ? '✅' : '❌'
      })
    })
    console.log('💖 ========================')

  } catch (error) {
    console.error('❌ レシピ取得エラー:', error)
  }
}
const emergencyDebug = () => {
  console.log('🚨 緊急デバッグ開始')
  console.log('💖 現在のストア:', Array.from(favoriteStore.value))
  console.log('💖 ストアの実際の型:', Object.prototype.toString.call(favoriteStore.value))
  
  // 強制的にレシピ9を追加してテスト
  console.log('🧪 テスト: レシピ9を強制追加')
  favoriteStore.value.add(9)
  console.log('💖 追加後のストア:', Array.from(favoriteStore.value))
  
  // レシピ9の表示を強制更新
  const recipe9 = recipes.value.find(r => r.id === 9)
  if (recipe9) {
    recipe9.isFavorited = true
    console.log('🧪 レシピ9の表示を強制更新')
  }
}

// 🔧 syncFavoriteStatus関数も改善
const syncFavoriteStatus = () => {
  if (recipes.value.length === 0) {
    console.log('🔄 レシピデータがないため同期をスキップ')
    return
  }

  console.log('🔄 syncFavoriteStatus 開始')
  let changedCount = 0
  
  recipes.value.forEach(recipe => {
    const wasLiked = recipe.isFavorited
    const shouldBeLiked = favoriteStore.value.has(recipe.id)
    
    if (wasLiked !== shouldBeLiked) {
      recipe.isFavorited = shouldBeLiked
      changedCount++
      console.log(`🔄 レシピ ${recipe.id}: ${wasLiked} → ${shouldBeLiked}`)
    }
  })
  
  if (changedCount > 0) {
    console.log(`🔄 ${changedCount}件の変更を適用`)
  } else {
    console.log('🔄 変更なし')
  }
}

// 一覧ページ（index.vue）のtoggleFavorite関数を以下に完全に置き換えてください

const toggleLike = async (recipe, event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }

  console.log('🖱️ ハートクリック:', recipe.title)

  if (!user.value) {
    alert('ログインが必要です')
    return
  }

  // 元の状態を保存
  const originalState = recipe.isFavorited
  const originalLikes = recipe.likes

  // 🔧 楽観的更新（即座にUIを更新）
  recipe.isFavorited = !originalState
  recipe.likes = originalState ? recipe.likes - 1 : recipe.likes + 1

  try {
    const { $auth } = useNuxtApp()
    const token = await $auth.currentUser.getIdToken(true)
    const config = useRuntimeConfig()

    console.log('📡 API呼び出し開始...')
    const response = await $fetch(`${config.public.apiBase}/api/recipes/${recipe.id}/toggle-like`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })

    console.log('✅ いいね切り替え成功:', response)

    // 🔧 APIレスポンスで最終的な状態を確定
    if (response && typeof response.is_liked !== 'undefined') {
      const newLikedState = Boolean(response.is_liked)
      const newLikesCount = response.likes_count || 0

      // UI更新（APIレスポンスに基づく最終更新）
      recipe.isFavorited = newLikedState
      recipe.likes = newLikesCount

      // 🔧 重要: グローバルストア更新
      if (newLikedState) {
        favoriteStore.value.add(recipe.id)
        console.log(`💖 レシピ${recipe.id}をお気に入りに追加（ストア更新）`)
      } else {
        favoriteStore.value.delete(recipe.id)
        console.log(`💔 レシピ${recipe.id}をお気に入りから削除（ストア更新）`)
      }

      // 🔧 追加: お気に入りページへの変更通知
      console.log('📢 お気に入りページへ変更を通知')
      
      // ストア変更を強制的にトリガー（他のページが監視している）
      favoriteStore.value = new Set(favoriteStore.value)

      console.log('💖 更新後のお気に入りストア:', Array.from(favoriteStore.value))
    }

  } catch (error) {
    console.error('❌ いいね更新エラー:', error)

    // 🔧 エラー時は元の状態に戻す（楽観的更新のロールバック）
    recipe.isFavorited = originalState
    recipe.likes = originalLikes

    // ストアも元の状態に戻す
    if (originalState) {
      favoriteStore.value.add(recipe.id)
    } else {
      favoriteStore.value.delete(recipe.id)
    }

    alert('いいねの更新に失敗しました')
  }
}



// URLクエリの監視
watch(() => route.query, (newQuery, oldQuery) => {
  // 🔧 実際に値が変わった場合のみ更新
  const newKeyword = newQuery.keyword || ''
  const newPage = parseInt(newQuery.page) || 1
  
  const oldKeyword = searchKeyword.value
  const oldPage = currentPage.value

  let shouldFetch = false

  if (newKeyword !== oldKeyword) {
    searchKeyword.value = newKeyword
    shouldFetch = true
    console.log('🔍 検索キーワード変更:', oldKeyword, '→', newKeyword)
  }

  if (newPage !== oldPage) {
    currentPage.value = newPage
    shouldFetch = true
    console.log('📄 ページ変更:', oldPage, '→', newPage)
  }

  if (shouldFetch) {
    console.log('🔄 URLクエリ変更によりデータ再取得')
    fetchRecipes()
  }
}, { immediate: false }) // 初回実行を防ぐ

watch(favoriteStore, (newFavorites) => {
  console.log('🔄 一覧ページ: ストア変更を検知')
  
  // 現在表示中のレシピの状態を同期
  recipes.value.forEach(recipe => {
    const shouldBeFavorited = newFavorites.has(recipe.id)
    if (recipe.isFavorited !== shouldBeFavorited) {
      console.log(`🔄 レシピ${recipe.id}の状態を同期: ${recipe.isFavorited} → ${shouldBeFavorited}`)
      recipe.isFavorited = shouldBeFavorited
    }
  })
}, { deep: true })



const fetchUserFavorites = async () => {
  try {
    const { $auth } = useNuxtApp()
    const token = await $auth.currentUser.getIdToken()

    const response = await $fetch('/api/user/liked-recipes', {
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

const debugAuth = async () => {
  try {
    const { $auth } = useNuxtApp()
    console.log('🔍 認証デバッグ開始')
    console.log('User:', user.value)
    console.log('IsLoggedIn:', isLoggedIn.value)

    if ($auth.currentUser) {
      const token = await $auth.currentUser.getIdToken()
      console.log('Token preview:', token.substring(0, 50) + '...')

      // トークンの有効性をテスト
      const testResponse = await $fetch('http://localhost/api/auth/check', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      })
      console.log('認証テスト結果:', testResponse)
    }
  } catch (error) {
    console.error('認証デバッグエラー:', error)
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
  padding: 8px 12px;
  position: relative;
  z-index: 10;
}

.recipe-card {
  position: relative;
  z-index: 1;
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

  cursor: pointer;
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