<template>
  <div class="recipe-create-container">
    <!-- 左カラム -->
    <div class="left-column">
      <h2 class="recipe-title-heading">{{ recipe.title || 'レシピ名を入力' }}</h2>

      <div class="image-preview" id="preview">
        <span v-if="!recipe.image" id="preview-text">No Image</span>
        <img
          v-else
          :src="recipe.image"
          alt="レシピ画像"
          id="preview-image"
        />
      </div>

      <div class="comment-section">
        <ul id="comment-list">
          <li
            v-for="comment in displayedComments"
            :key="comment.id"
            class="comment-item"
          >
            <img
              v-if="getAvatarUrl(comment.user)"
              :src="getAvatarUrl(comment.user)"
              class="comment-avatar"
              alt="avatar"
            >
            <i
              v-else
              class="fas fa-user comment-avatar-icon"
            ></i>
            <span class="username" :title="comment.user.name">{{ truncateUsername(comment.user.name) }}</span>
            <span class="comment-body">{{ comment.body }}</span>
          </li>
        </ul>

        <!-- もっと見る/折りたたみボタン -->
        <div v-if="hasMoreComments" class="comment-toggle-section">
          <button 
            v-if="!showAllComments" 
            @click="showAllComments = true"
            class="comment-toggle-btn"
          >
            もっと見る ({{ remainingCount }}件)
          </button>
          <button 
            v-else 
            @click="showAllComments = false"
            class="comment-toggle-btn"
          >
            表示を折りたたむ
          </button>
        </div>

        <div class="comment-wrapper">
          <textarea
            v-model="newComment"
            ref="commentTextarea"
            id="comment-box"
            class="auto-resize"
            :class="{ 'error': commentError }"
            placeholder="コメントを記入..."
            @input="handleCommentInput"
            :disabled="isSubmitting"
            maxlength="500"
          ></textarea>

          <div class="comment-counter">
            <span :class="{ 'warning': commentLength > 450, 'error': commentLength > 500 }">
              {{ commentLength }}/500
            </span>
          </div>
          
          <!-- エラーメッセージ -->
          <div v-if="commentError" class="error-message">
            {{ commentError }}
          </div>

          <button
            type="button"
            class="send-button"
            :class="{ 'disabled': !!commentError || !newComment.trim() || isSubmitting }"
            :disabled="!!commentError || !newComment.trim() || isSubmitting"
            title="送信"
            @click="submitComment"
          >
            <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
            <i v-else class="far fa-paper-plane"></i>
          </button>
        </div>

        <div class="action-buttons">
          <button
            class="icon-button"
            @click="toggleLike"
          >
            <i
              :class="recipe.isLiked ? 'fas fa-heart heart-icon-filled' : 'far fa-heart heart-icon-outline'"
            ></i>
            <span class="like-count">{{ recipe.likes }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 右カラム -->
    <div class="right-column">
      <div class="recipe-form">
        <label>ジャンル</label>
        <div class="recipe-info">{{ recipe.genre }}</div>

        <label>材料（{{ recipe.servings || '人数' }}人分）</label>
        <div id="ingredients">
          <div
            v-for="ingredient in recipe.ingredients"
            :key="ingredient.id"
            class="ingredient-row"
          >
            <div class="ingredient-name">{{ ingredient.name }}</div>
            <div class="ingredient-qty">{{ ingredient.quantity }}</div>
          </div>
        </div>

        <label>作り方</label>
        <div class="recipe-body">{{ recipe.body }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useRoute, useHead, navigateTo } from '#app'

// 認証関連
const { user, isLoggedIn, initAuth } = useAuth()

// Head設定
useHead({
  title: 'レシピ詳細',
  link: [
    {
      rel: 'stylesheet',
      href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'
    }
  ]
})

const route = useRoute()
const recipeId = parseInt(route.params.id)

const getImageUrl = (imageUrl) => {
    if (!imageUrl) return '/images/no-image.png'
    
    if (imageUrl.startsWith('/storage/')) {
        return `http://localhost${imageUrl}`
    }
    
    return imageUrl
}

// データ定義
const newComment = ref('')
const commentTextarea = ref(null)
const showAllComments = ref(false)
const commentError = ref('')
const isSubmitting = ref(false)
const recipe = ref({})

// ✅ アバターURL取得関数を追加
const getAvatarUrl = (user) => {
  console.log('🔍 getAvatarUrl 実行:', {
    user: user,
    avatar_path: user?.avatar_path
  })
  
  if (!user || !user.avatar_path) {
    console.log('❌ アバターパスなし')
    return null
  }
  
  // フルURLの場合
  if (user.avatar_path.startsWith('http://') || user.avatar_path.startsWith('https://')) {
    console.log('✅ フルURL使用:', user.avatar_path)
    return user.avatar_path
  }
  
  // 相対パス（/storage/で始まる）の場合
  if (user.avatar_path.startsWith('/storage/')) {
    const fullUrl = `http://localhost${user.avatar_path}`
    console.log('✅ 相対パス→フルURL:', fullUrl)
    return fullUrl
  }
  
  // ファイル名のみの場合
  const fileName = user.avatar_path.includes('/') 
    ? user.avatar_path.split('/').pop() 
    : user.avatar_path
  
  const fallbackUrl = `http://localhost/storage/avatars/${fileName}`
  console.log('⚠️ フォールバックURL使用:', fallbackUrl)
  return fallbackUrl
}

// デバッグ用：レシピデータの変更を監視
watch(recipe, (newRecipe) => {
  console.log('🔄 レシピデータが更新されました:', {
    title: newRecipe.title,
    genre: newRecipe.genre,
    servings: newRecipe.servings,
    body: newRecipe.body,
    ingredients: newRecipe.ingredients
  })
}, { deep: true })

// モックレシピデータ
const recipeDatabase = {
  1: {
    id: 1,
    title: '基本のハンバーグ',
    genre: '肉料理',
    servings: '4人分',
    image: null,
    body: '1. 玉ねぎをみじん切りにして炒め、冷ましておく\n2. ボウルにひき肉、卵、パン粉、牛乳を入れて混ぜる\n3. 炒めた玉ねぎ、塩こしょう、ナツメグを加えてよく混ぜる\n4. 4等分して楕円形に成形する\n5. フライパンで両面を焼き、蓋をして中まで火を通す',
    likes: 23,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '牛ひき肉', quantity: '400g' },
      { id: 2, name: '玉ねぎ', quantity: '1個' },
      { id: 3, name: '卵', quantity: '1個' },
      { id: 4, name: 'パン粉', quantity: '1/2カップ' },
      { id: 5, name: '牛乳', quantity: '大さじ2' },
      { id: 6, name: '塩こしょう', quantity: '適量' },
      { id: 7, name: 'ナツメグ', quantity: '少々' }
    ]
  },
  2: {
    id: 2,
    title: 'チキンカレー',
    genre: 'カレー',
    servings: '3人分',
    image: null,
    body: '1. 鶏肉を一口大に切る\n2. 野菜を食べやすい大きさに切る\n3. 鍋で鶏肉を炒め、色が変わったら野菜を加える\n4. 水とトマト缶を加えて煮込む\n5. 野菜が柔らかくなったらカレールーを溶かし入れる\n6. 10分程度煮込んで完成',
    likes: 35,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '鶏もも肉', quantity: '400g' },
      { id: 2, name: '玉ねぎ', quantity: '2個' },
      { id: 3, name: 'にんじん', quantity: '1本' },
      { id: 4, name: 'じゃがいも', quantity: '2個' },
      { id: 5, name: 'トマト缶', quantity: '1缶' },
      { id: 6, name: 'カレールー', quantity: '1/2箱' },
      { id: 7, name: '水', quantity: '400ml' },
      { id: 8, name: 'サラダ油', quantity: '大さじ1' }
    ]
  },
  3: {
    id: 3,
    title: '和風パスタ',
    genre: '麺類',
    servings: '2人分',
    image: null,
    body: '1. パスタを茹でる\n2. ベーコンを切って炒める\n3. しめじを加えて炒める\n4. 茹で上がったパスタを加える\n5. 醤油とバターで味付けし、大葉をトッピング',
    likes: 12,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'スパゲッティ', quantity: '200g' },
      { id: 2, name: 'しめじ', quantity: '1パック' },
      { id: 3, name: 'ベーコン', quantity: '3枚' },
      { id: 4, name: '大葉', quantity: '5枚' },
      { id: 5, name: '醤油', quantity: '大さじ2' },
      { id: 6, name: 'バター', quantity: '15g' },
      { id: 7, name: '塩こしょう', quantity: '適量' }
    ]
  },
  4: {
    id: 4,
    title: 'チョコレートケーキ',
    genre: 'デザート',
    servings: '5人分以上',
    image: null,
    body: '1. オーブンを180度に予熱する\n2. バターを溶かす\n3. 卵と砂糖を混ぜる\n4. 粉類をふるって加える\n5. バターと牛乳を加えて混ぜる\n6. 型に入れて30分焼く',
    likes: 28,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '薄力粉', quantity: '100g' },
      { id: 2, name: 'ココアパウダー', quantity: '30g' },
      { id: 3, name: '卵', quantity: '2個' },
      { id: 4, name: '砂糖', quantity: '80g' },
      { id: 5, name: 'バター', quantity: '50g' },
      { id: 6, name: '牛乳', quantity: '50ml' },
      { id: 7, name: 'ベーキングパウダー', quantity: '小さじ1' }
    ]
  },
  5: {
    id: 5,
    title: '野菜炒め',
    genre: '野菜料理',
    servings: '2人分',
    image: null,
    body: '1. 野菜を食べやすい大きさに切る\n2. フライパンで豚肉を炒める\n3. 野菜を加えて炒める\n4. 醤油と塩こしょうで味付け\n5. 最後にごま油を回しかける',
    likes: 9,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'キャベツ', quantity: '1/4個' },
      { id: 2, name: 'にんじん', quantity: '1/2本' },
      { id: 3, name: 'ピーマン', quantity: '2個' },
      { id: 4, name: 'もやし', quantity: '1袋' },
      { id: 5, name: '豚こま肉', quantity: '150g' },
      { id: 6, name: '醤油', quantity: '大さじ1' },
      { id: 7, name: '塩こしょう', quantity: '適量' },
      { id: 8, name: 'ごま油', quantity: '大さじ1' }
    ]
  },
  6: {
    id: 6,
    title: 'グラタン',
    genre: '洋食',
    servings: '4人分',
    image: null,
    body: '1. マカロニを茹でる\n2. 玉ねぎを薄切りにする\n3. ホワイトソースを作る\n4. 具材を混ぜ合わせる\n5. チーズをのせる\n6. オーブンで焼いて完成',
    likes: 19,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'マカロニ', quantity: '200g' },
      { id: 2, name: '鶏肉', quantity: '150g' },
      { id: 3, name: '玉ねぎ', quantity: '1個' },
      { id: 4, name: 'バター', quantity: '30g' },
      { id: 5, name: '小麦粉', quantity: '大さじ3' },
      { id: 6, name: '牛乳', quantity: '400ml' },
      { id: 7, name: 'チーズ', quantity: '100g' },
      { id: 8, name: '塩こしょう', quantity: '適量' }
    ]
  },
  7: {
    id: 7,
    title: 'ゆかりおにぎり',
    genre: '和食',
    servings: '2人分',
    image: null,
    body: '1. ご飯を炊く\n2. ゆかりをご飯に混ぜ込む\n3. 手を軽く濡らす\n4. ご飯を三角形に握る\n5. 海苔を巻いて完成',
    likes: 12,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'ご飯', quantity: '2杯' },
      { id: 2, name: 'ゆかり', quantity: '大さじ1' },
      { id: 3, name: '海苔', quantity: '2枚' },
      { id: 4, name: '塩', quantity: '少々' }
    ]
  },
  8: {
    id: 8,
    title: '唐揚げ',
    genre: '和食',
    servings: '3人分',
    image: null,
    body: '1. 鶏肉を一口大に切る\n2. 醤油、酒、生姜で下味をつける\n3. 片栗粉をまぶす\n4. 170度の油で揚げる\n5. 一度取り出して2度揚げする\n6. 油を切って完成',
    likes: 28,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '鶏もも肉', quantity: '400g' },
      { id: 2, name: '醤油', quantity: '大さじ2' },
      { id: 3, name: '酒', quantity: '大さじ1' },
      { id: 4, name: '生姜', quantity: '1片' },
      { id: 5, name: '片栗粉', quantity: '適量' },
      { id: 6, name: 'サラダ油', quantity: '適量' }
    ]
  },
  9: {
    id: 9,
    title: '味噌汁',
    genre: '和食',
    servings: '4人分',
    image: null,
    body: '1. だしを取る\n2. 豆腐とわかめを用意する\n3. だしを沸騰させる\n4. 具材を入れて煮る\n5. 味噌を溶き入れる\n6. ネギを散らして完成',
    likes: 7,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'だし', quantity: '800ml' },
      { id: 2, name: '味噌', quantity: '大さじ3' },
      { id: 3, name: '豆腐', quantity: '1/2丁' },
      { id: 4, name: 'わかめ', quantity: '適量' },
      { id: 5, name: 'ネギ', quantity: '1本' }
    ]
  },
  10: {
    id: 10,
    title: '焼きそば',
    genre: '中華',
    servings: '2人分',
    image: null,
    body: '1. 野菜を切る\n2. 麺を茹でる\n3. フライパンで野菜を炒める\n4. 麺を加えて炒める\n5. ソースを絡める\n6. 青のりをかけて完成',
    likes: 18,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '焼きそば麺', quantity: '2玉' },
      { id: 2, name: 'キャベツ', quantity: '1/4個' },
      { id: 3, name: '人参', quantity: '1/2本' },
      { id: 4, name: 'もやし', quantity: '1袋' },
      { id: 5, name: '豚こま肉', quantity: '100g' },
      { id: 6, name: '焼きそばソース', quantity: '1袋' },
      { id: 7, name: '青のり', quantity: '適量' }
    ]
  },
  11: {
    id: 11,
    title: 'チャーハン',
    genre: '中華',
    servings: '2人分',
    image: null,
    body: '1. ご飯を冷ます\n2. 卵を溶きほぐす\n3. フライパンで卵を炒める\n4. ご飯を加えて炒める\n5. 調味料で味付けする\n6. ネギを散らして完成',
    likes: 22,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'ご飯', quantity: '2杯' },
      { id: 2, name: '卵', quantity: '2個' },
      { id: 3, name: 'ハム', quantity: '2枚' },
      { id: 4, name: 'ネギ', quantity: '1本' },
      { id: 5, name: '醤油', quantity: '大さじ1' },
      { id: 6, name: '塩こしょう', quantity: '適量' },
      { id: 7, name: 'ごま油', quantity: '小さじ1' }
    ]
  },
  12: {
    id: 12,
    title: 'オムライス',
    genre: '洋食',
    servings: '2人分',
    image: null,
    body: '1. チキンライスを作る\n2. 卵を溶きほぐす\n3. フライパンで卵を焼く\n4. チキンライスを包む\n5. ケチャップをかける\n6. パセリを散らして完成',
    likes: 35,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'ご飯', quantity: '2杯' },
      { id: 2, name: '卵', quantity: '4個' },
      { id: 3, name: '鶏肉', quantity: '100g' },
      { id: 4, name: '玉ねぎ', quantity: '1/2個' },
      { id: 5, name: 'ケチャップ', quantity: '大さじ4' },
      { id: 6, name: 'バター', quantity: '20g' },
      { id: 7, name: '塩こしょう', quantity: '適量' },
      { id: 8, name: 'パセリ', quantity: '少々' }
    ]
  }
}

// お気に入り状態管理用のグローバルストア（一覧ページと同じ）
const favoriteStore = useState('favorites', () => new Set())

// コメントデータ（グローバルストアで管理して永続化）
// const commentsStore = useState('comments', () => new Map())

// 現在のレシピのコメント
// const comments = computed(() => {
//   const recipeComments = commentsStore.value.get(recipeId) || []
//   return recipeComments
// })

const comments = ref([])
const commentsLoading = ref(false)

// 表示するコメントを制御
const displayedComments = computed(() => {
  if (showAllComments.value) {
    return [...comments.value]
  } else {
    return [...comments.value].slice(0, 3)
  }
})

// 残りのコメント数
const remainingCount = computed(() => {
  return Math.max(0, comments.value.length - 3)
})

// もっと見るボタンの表示判定
const hasMoreComments = computed(() => {
  return comments.value.length > 3
})

// ⭐ 文字数計算を追加
const commentLength = computed(() => {
  return newComment.value.length
})

// ユーザー名の省略処理
const truncateUsername = (username) => {
  if (!username) return 'ユーザー'
  return username.length > 10 ? username.substring(0, 10) + '...' : username
}


// ⭐ バリデーション関数を追加
const validateComment = (comment) => {
  const trimmed = comment.trim()
  
  if (!trimmed) {
    return 'コメントを入力してください'
  }
  
  if (trimmed.length < 1) {
    return 'コメントは1文字以上で入力してください'
  }
  
  if (trimmed.length > 500) {
    return 'コメントは500文字以内で入力してください'
  }
  
  // 連続する同じ文字のチェック（例：「あああああああああああ」）
  if (/(.)\1{9,}/.test(trimmed)) {
    return '同じ文字の連続は10文字までにしてください'
  }
  
  return null // バリデーション通過
}

// ⭐ リアルタイムバリデーション関数を追加
const handleCommentInput = () => {
  commentError.value = ''
  autoResize()
  
  // 文字数チェック（リアルタイム）
  if (newComment.value.length > 500) {
    commentError.value = 'コメントは500文字以内で入力してください'
  }
}


// いいねボタンの切り替え（API対応版）
// 詳細ページ（show/[id].vue）のtoggleLike関数を以下に完全に置き換えてください

const toggleLike = async () => {
  if (!user.value) {
    console.log('⚠️ ログインが必要です')
    return
  }

  // 元の状態を保存（エラー時の復元用）
  const originalLiked = recipe.value.isLiked
  const originalLikes = recipe.value.likes

  // 🔧 楽観的更新（即座にUIを更新）
  recipe.value.isLiked = !originalLiked
  recipe.value.likes = originalLiked ? recipe.value.likes - 1 : recipe.value.likes + 1

  try {
    console.log('💖 いいね切り替え開始...')
    
    const config = useRuntimeConfig()
    const { $auth } = useNuxtApp()
    const token = await $auth.currentUser.getIdToken()
    
    const response = await $fetch(`${config.public.apiBase}/api/recipes/${recipe.value.id}/toggle-like`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })
    
    console.log('💖 いいね API応答:', response)
    
    // 🔧 APIレスポンスで最終的な状態を確定
    const newLikedState = Boolean(response.is_liked)
    const newLikesCount = response.likes_count || 0

    // UI更新（APIレスポンスに基づく最終更新）
    recipe.value.isLiked = newLikedState
    recipe.value.likes = newLikesCount

    // 🔧 重要: グローバルストア更新
    if (newLikedState) {
      favoriteStore.value.add(recipe.value.id)
      console.log(`💖 レシピ${recipe.value.id}「${recipe.value.title}」をお気に入りに追加（ストア更新）`)
    } else {
      favoriteStore.value.delete(recipe.value.id)
      console.log(`💔 レシピ${recipe.value.id}「${recipe.value.title}」をお気に入りから削除（ストア更新）`)
    }

    // 🔧 追加: お気に入りページへの変更通知
    console.log('📢 お気に入りページへ変更を通知')
    
    // ストア変更を強制的にトリガー（他のページが監視している）
    favoriteStore.value = new Set(favoriteStore.value)

    console.log('💖 現在のお気に入り:', Array.from(favoriteStore.value))
    console.log('💖 現在のいいね数:', recipe.value.likes)
      
  } catch (error) {
    console.error('❌ いいね切り替えエラー:', error)
    
    // 🔧 エラー時は元の状態に戻す（楽観的更新のロールバック）
    recipe.value.isLiked = originalLiked
    recipe.value.likes = originalLikes
    
    // ストアも元の状態に戻す
    if (originalLiked) {
      favoriteStore.value.add(recipe.value.id)
    } else {
      favoriteStore.value.delete(recipe.value.id)
    }
    
    if (error.status === 401) {
      console.log('⚠️ 認証エラー - ログインページにリダイレクト')
      await navigateTo('/auth/login')
    } else {
      console.log('⚠️ いいね機能でエラーが発生しました')
      alert('いいねの更新に失敗しました。もう一度お試しください。')
    }
  }
}

// ✅ APIからコメント一覧を取得
const fetchComments = async () => {
  commentsLoading.value = true
  try {
    console.log('💬 コメント一覧を取得中...')
    
    const config = useRuntimeConfig()
    const { $auth } = useNuxtApp()
    const token = await $auth.currentUser.getIdToken()
    
    const response = await $fetch(`${config.public.apiBase}/api/recipes/${recipeId}/comments`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })

    console.log('💬 コメント取得応答:', response)

    // APIレスポンスからコメントデータを変換
    const apiComments = response.data || []
    console.log('💬 取得したコメント数:', apiComments.length)

    const convertedComments = apiComments.map(comment => ({
      id: comment.id,
      user: {
        name: comment.user?.name || 'ユーザー',
        avatar_path: comment.user?.avatar_url || null
      },
      body: comment.content, // APIでは'content'、フロントでは'body'
      createdAt: comment.created_at
    }))

    comments.value = convertedComments
    console.log('✅ コメント一覧読み込み完了:', convertedComments.length, '件')

  } catch (error) {
    console.error('❌ コメント取得エラー:', error)
    comments.value = []
  } finally {
    commentsLoading.value = false
  }
}

// ⭐ コメント送信関数（API対応版）
const submitComment = async () => {
  if (!user.value) {
    console.log('⚠️ ログインが必要です')
    return
  }

  const validationError = validateComment(newComment.value)
  if (validationError) {
    commentError.value = validationError
    return
  }

  if (isSubmitting.value) return
  isSubmitting.value = true

  try {
    console.log('💬 コメント投稿開始:', newComment.value.trim())
    
    const config = useRuntimeConfig()
    const { $auth } = useNuxtApp()
    const token = await $auth.currentUser.getIdToken()
    
    const response = await $fetch(`${config.public.apiBase}/api/recipes/${recipeId}/comments`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: {
        content: newComment.value.trim()
      }
    })

    console.log('💬 コメント投稿応答:', response)

    // ✅ 重要：投稿後にAPIから最新のコメント一覧を再取得
    await fetchComments()
    
    newComment.value = ''
    commentError.value = ''

    console.log('✅ コメント投稿成功:', response.message)

    // textareaをリセット
    autoResize()
      
  } catch (error) {
    console.error('❌ コメント投稿エラー:', error)
    
    if (error.status === 403) {
      commentError.value = '管理者はコメントできません'
    } else if (error.status === 429) {
      commentError.value = '1分以内の連続投稿はできません'
    } else if (error.data?.errors?.content) {
      commentError.value = error.data.errors.content[0]
    } else {
      commentError.value = 'コメントの送信に失敗しました'
    }
  } finally {
    isSubmitting.value = false
  }
}


// textareaの自動リサイズ
const autoResize = () => {
  nextTick(() => {
    if (commentTextarea.value) {
      commentTextarea.value.style.height = 'auto'
      commentTextarea.value.style.height = commentTextarea.value.scrollHeight + 'px'
    }
  })
}



onMounted(async () => {
  console.log('🔍 /user/show ページの認証チェック開始...')

  try {
    await initAuth()
    console.log('👤 認証チェック結果:', user.value ? user.value.email : 'null')

    if (!isLoggedIn.value || !user.value) {
      console.log('⚠️ 認証失敗 - ログインページにリダイレクト')
      await navigateTo('/auth/login')
      return
    }

    console.log('✅ 認証成功:', user.value.email, 'レシピ詳細ページを表示')

    // 🔧 共通の設定を先に取得
    const config = useRuntimeConfig()
    const { $auth } = useNuxtApp()
    const token = await $auth.currentUser.getIdToken()


    // レシピデータの取得
    try {
      const response = await $fetch(`${config.public.apiBase}/api/recipes/${recipeId}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      })

      console.log('📦 完全なAPI応答:', response)


      // 🔧 重要：responseの中のdataプロパティにアクセス
      const recipeData = response.data || response
      console.log('📦 実際のレシピデータ:', recipeData)

      // 🔧 dataプロパティの中身を使用して設定
      recipe.value = {
        id: recipeData.id,
        title: recipeData.title,
        genre: recipeData.genre,
        servings: recipeData.servings,
        image: getImageUrl(recipeData.image_url),
        body: recipeData.instructions,
        likes: recipeData.likes_count,
        isLiked: recipeData.is_liked || false,
        ingredients: parseIngredients(recipeData.ingredients || '')
      }

      console.log('✅ API データ設定完了:', recipe.value)

    } catch (apiError) {
      console.error('❌ レシピAPI取得エラー:', apiError)
      console.error('❌ エラーの詳細:', {
        message: apiError.message,
        status: apiError.status,
        statusText: apiError.statusText,
        data: apiError.data
      })

      // APIエラー時はモックデータにフォールバック
      console.log('📋 フォールバック：モックデータを使用')
      const recipeData = recipeDatabase[recipeId]

      if (!recipeData) {
        console.log('❌ レシピが見つかりません（ID:', recipeId, '）')
        alert(`レシピ（ID: ${recipeId}）が見つかりません`)
        await navigateTo('/user')
        return
      }

      recipe.value = { ...recipeData }
      console.log('📖 モックデータ読み込み完了:', recipe.value.title)
    }

    await fetchComments()

    // // 🔧 コメントデータの取得
    // try {
    //   console.log('💬 コメントデータを読み込み中...')

    //   const commentsResponse = await $fetch(`${config.public.apiBase}/api/recipes/${recipeId}/comments`, {
    //     method: 'GET',
    //     headers: {
    //       'Authorization': `Bearer ${token}`,
    //       'Content-Type': 'application/json'
    //     }
    //   })

    //   console.log('💬 コメントAPI応答:', commentsResponse)

    //   // APIレスポンスからコメントデータを変換
    //   const apiComments = commentsResponse.data || []
    //   console.log('💬 取得したコメント数:', apiComments.length)

    //   const convertedComments = apiComments.map(comment => {
    //     console.log('🔧 コメント変換:', comment) // デバッグ用

    //     return {
    //       id: comment.id,
    //       user: {
    //         name: comment.user?.name || 'ユーザー',
    //         // 🔧 重要：APIから取得したavatar_urlを使用
    //         avatar_path: comment.user?.avatar_url || null
    //       },
    //       body: comment.content, // APIでは'content'、フロントでは'body'
    //       createdAt: comment.created_at
    //     }
    //   })


    //   // コメントをストアに設定
    //   commentsStore.value.set(recipeId, convertedComments)
    //   console.log('✅ APIコメント読み込み完了:', convertedComments.length, '件')

    //   // デバッグ：アバター情報を確認
    //   convertedComments.forEach((comment, index) => {
    //     console.log(`💬 コメント${index + 1}:`, {
    //       user: comment.user.name,
    //       avatar: comment.user.avatar_path
    //     })
    //   })

    // } catch (commentError) {
    //   console.error('❌ コメント取得エラー:', commentError)

    //   // エラー時はモックコメントを使用
    //   console.log('📋 フォールバック：モックコメントを使用')
    //   const initialComments = [
    //     {
    //       id: 1,
    //       user: { name: 'ユーザーA', avatar_path: null },
    //       body: 'めっちゃ美味しかったです！',
    //       createdAt: new Date('2025-01-01').toISOString()
    //     },
    //     {
    //       id: 2,
    //       user: { name: 'ユーザーB', avatar_path: null },
    //       body: '今度作ってみます〜',
    //       createdAt: new Date('2025-01-02').toISOString()
    //     },
    //     {
    //       id: 3,
    //       user: { name: 'ユーザーC', avatar_path: null },
    //       body: '今日の献立に取り入れようと思います。',
    //       createdAt: new Date('2025-01-03').toISOString()
    //     }
    //   ]
    //   commentsStore.value.set(recipeId, initialComments)
    // }

    // お気に入り状態を同期
    recipe.value.isLiked = favoriteStore.value.has(recipe.value.id)

    console.log('💖 お気に入り状態:', recipe.value.isLiked)
    console.log('💬 コメント数:', comments.value.length)

    // 初期のtextareaリサイズ
    autoResize()

  } catch (error) {
    console.error('❌ 認証処理エラー:', error)
    await navigateTo('/auth/login')
  }
})

// お気に入りストアの変更を監視（他のページからの変更を反映）
watch(favoriteStore, (newFavorites) => {
  if (recipe.value.id) {
    const shouldBeLiked = newFavorites.has(recipe.value.id)
    if (recipe.value.isLiked !== shouldBeLiked) {
      console.log(`🔄 詳細ページ: レシピ${recipe.value.id}の状態を同期: ${recipe.value.isLiked} → ${shouldBeLiked}`)
      recipe.value.isLiked = shouldBeLiked
    }
  }
}, { deep: true })



// 🔧 改善版：材料文字列を配列に変換する関数
const parseIngredients = (ingredientsString) => {
  if (!ingredientsString || typeof ingredientsString !== 'string') {
    console.log('⚠️ parseIngredients: 無効な入力:', ingredientsString)
    return []
  }

  console.log('🔍 parseIngredients 入力:', ingredientsString)

  const lines = ingredientsString.split('\n').filter(line => line.trim())
  
  const result = lines.map((line, index) => {
    const trimmedLine = line.trim()
    
    // "材料名 分量" の形式を想定してスペースで分割
    const lastSpaceIndex = trimmedLine.lastIndexOf(' ')
    
    if (lastSpaceIndex > 0) {
      const name = trimmedLine.substring(0, lastSpaceIndex).trim()
      const quantity = trimmedLine.substring(lastSpaceIndex + 1).trim()
      
      console.log(`🔍 材料${index + 1}: "${name}" - "${quantity}"`)
      
      return {
        id: index + 1,
        name: name,
        quantity: quantity
      }
    } else {
      // スペースがない場合はそのまま材料名として扱う
      console.log(`🔍 材料${index + 1}: "${trimmedLine}" - 分量なし`)
      
      return {
        id: index + 1,
        name: trimmedLine,
        quantity: ''
      }
    }
  })
  
  console.log('🔍 parseIngredients 結果:', result)
  return result
}
</script>

<style scoped>
@import "@/assets/css/common.css";

/* 全体の2カラムレイアウト */
.recipe-create-container {
    display: flex;
    padding: 40px;
    gap: 40px;
    justify-content: center;
    align-items: stretch;
    width: 100%;
}

/* 左カラム */
.left-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 300px;
    min-width: 300px;
    flex-shrink: 0;
    gap: 30px;
}

/* 料理名（画像の上） */
.recipe-title-heading {
    font-size: 20px;
    font-weight: 500;
    margin-bottom: 10px;
    text-align: center;
}

/* プレビューエリア */
.image-preview {
    width: 100%;
    aspect-ratio: 1 / 1;
    background-color: #f0f0f0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    height: 300px;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* コメント入力エリア */
.comment-wrapper {
    position: relative;
    width: 100%;
    display: inline-block;
}

#comment-box {
    width: 100%;
    padding: 10px 50px 10px 10px;
    resize: none;
    overflow: hidden;
    font-size: 14px;
    box-sizing: border-box;
    border-radius: 6px;
    border: 1px solid #aaa;
}

/* コメントリスト */
.comment-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.comment-avatar {
    object-fit: cover;
}

/* Font Awesome アバターアイコン用 */
.comment-avatar-icon,
.comment-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    margin: 8px;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #eee;
    color: #666;
}

.username {
    margin-right: 2px;
    font-size: 10px;
    white-space: nowrap;
    max-width: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 600;
    color: #333;
    cursor: default;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
}

.username:hover {
    color: #666;
}

.comment-body {
    flex: 1;
    font-size: 12px;
    font-family: sans-serif;
    line-height: 1.4;
    word-wrap: break-word;
}

/* コメント展開ボタン */
.comment-toggle-section {
    margin-top: 10px;
    margin-bottom: 10px;
    text-align: center;
}

.comment-toggle-btn {
    background: none;
    border: 1px solid #bbb;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 11px;
    color: #333;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
}

.comment-toggle-btn:hover {
    background-color: #f5f5f5;
    color: #333;
    border-color: #bbb;
}

/* エラー状態のテキストエリア */
#comment-box.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
}

/* 文字数カウンター */
.comment-counter {
    position: absolute;
    right: 50px;
    bottom: 12px;
    font-size: 10px;
    color: #666;
    pointer-events: none;
}

.comment-counter .warning {
    color: #ffc107;
}

.comment-counter .error {
    color: #dc3545;
    font-weight: bold;
}

/* エラーメッセージ */
.error-message {
    position: absolute;
    bottom: -20px;
    left: 0;
    font-size: 11px;
    color: #dc3545;
    background-color: #fff;
    padding: 2px 4px;
    border-radius: 3px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    z-index: 10;
    white-space: nowrap;
}

/* 送信ボタンの無効状態 */
.send-button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.send-button.disabled:hover {
    color: inherit;
}

/* テキストエリアの無効状態 */
#comment-box:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

/* スピナーアニメーション */
.fa-spin {
    animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* いいねボタン */
.icon-button {
    background: none;
    border: none;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-size: 14px;
    padding-right: 15px;
}

.like-count {
    font-size: 10px;
}

/* Font Awesome ハートアイコン */
.heart-icon-filled {
    color: #dc3545 !important;
    font-size: 18px !important;
}

.heart-icon-outline {
    color: #666 !important;
    font-size: 18px !important;
}

/* 送信ボタン */
.send-button {
    position: absolute;
    right: 12px;
    bottom: 12px;
    background: none;
    border: none;
    font-size: 14px;
    cursor: pointer;
    transform: translateY(0);
}

.send-button:hover {
    color: #000;
}

/* 右カラムのフォーム */
.recipe-form {
    width: 400px;
    min-height: 100%;
}

.recipe-form label {
    display: block;
    font-weight: bold;
    margin-top: 25px;
    margin-bottom: 10px;
}

/* 材料名と分量 */
.ingredient-name,
.ingredient-qty {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    box-sizing: border-box;
    background-color: transparent;
    border: none;
    border-bottom: 1px solid #ccc;
    border-radius: 0;
}

/* 材料名と分量を横並び */
.ingredient-row {
    display: flex;
    gap: 0px;
    margin-bottom: 10px;
}

/* 幅比率 */
.ingredient-name {
    flex: 2;
}

.ingredient-qty {
    flex: 1;
}

/* テキストエリア自動リサイズ */
.auto-resize {
    overflow: hidden;
    resize: none;
}

/* 詳細ページ専用スタイル */
.recipe-info {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 15px;
}

.recipe-body {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
    white-space: pre-wrap;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .recipe-create-container {
        flex-direction: column;
        padding: 20px;
        gap: 30px;
    }

    .left-column {
        width: 100%;
        min-width: auto;
        gap: 20px;
    }

    .recipe-form {
        width: 100%;
    }

    .image-preview {
        max-width: 280px;
        max-height: 280px;
    }

    .recipe-title-heading {
        font-size: 18px;
    }
}
</style>