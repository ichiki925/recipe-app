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
            <i
              v-if="!comment.user.avatar_path"
              class="fas fa-user comment-avatar-icon"
            ></i>
            <img
              v-else
              :src="comment.user.avatar_path"
              class="comment-avatar"
              alt="avatar"
            >
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
            placeholder="コメントを記入..."
            @input="autoResize"
          ></textarea>
          <button
            type="button"
            class="send-button"
            title="送信"
            @click="submitComment"
          >
            <i class="far fa-paper-plane"></i>
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
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute, useHead, navigateTo } from '#app'

// 認証関連
const { getCurrentUser, user, isLoggedIn, waitForAuth } = useAuth()

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

// データ定義
const newComment = ref('')
const commentTextarea = ref(null)
const showAllComments = ref(false)

// モックレシピデータ（実際はAPIから取得）
const recipeDatabase = {
  1: {
    id: 1,
    title: 'チキンカレー',
    genre: '和食',
    servings: 4,
    image: null,
    body: '美味しいチキンカレーの作り方です。\n\n1. 玉ねぎを薄切りにします\n2. 鶏肉を一口大に切ります\n3. 鍋で玉ねぎを炒めます\n4. 鶏肉を加えて炒めます\n5. 水を加えて煮込みます\n6. カレールーを加えて完成です',
    likes: 24,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '鶏もも肉', quantity: '300g' },
      { id: 2, name: '玉ねぎ', quantity: '1個' },
      { id: 3, name: 'カレールー', quantity: '1箱' },
      { id: 4, name: 'じゃがいも', quantity: '2個' },
      { id: 5, name: '人参', quantity: '1本' }
    ]
  },
  2: {
    id: 2,
    title: 'パスタボロネーゼ',
    genre: 'イタリアン',
    servings: 2,
    image: null,
    body: '本格的なボロネーゼパスタの作り方です。\n\n1. 玉ねぎ、人参、セロリをみじん切りにします\n2. ひき肉を炒めます\n3. 野菜を加えて炒めます\n4. トマト缶を加えて煮込みます\n5. パスタを茹でます\n6. ソースと絡めて完成です',
    likes: 15,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'パスタ', quantity: '200g' },
      { id: 2, name: '合いびき肉', quantity: '200g' },
      { id: 3, name: 'トマト缶', quantity: '1缶' },
      { id: 4, name: '玉ねぎ', quantity: '1/2個' },
      { id: 5, name: '人参', quantity: '1/2本' }
    ]
  },
  3: {
    id: 3,
    title: '麻婆豆腐',
    genre: '中華',
    servings: 3,
    image: null,
    body: '本格的な麻婆豆腐の作り方です。\n\n1. 豆腐を一口大に切ります\n2. ひき肉を炒めます\n3. 豆板醤を加えて炒めます\n4. 豆腐を加えて煮込みます\n5. 水溶き片栗粉でとろみをつけます\n6. ネギを散らして完成です',
    likes: 8,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '絹豆腐', quantity: '1丁' },
      { id: 2, name: '豚ひき肉', quantity: '150g' },
      { id: 3, name: '豆板醤', quantity: '大さじ1' },
      { id: 4, name: '長ネギ', quantity: '1本' },
      { id: 5, name: '片栗粉', quantity: '大さじ1' }
    ]
  },
  4: {
    id: 4,
    title: 'ハンバーグ',
    genre: '洋食',
    servings: 4,
    image: null,
    body: 'ジューシーなハンバーグの作り方です。\n\n1. 玉ねぎをみじん切りにして炒めます\n2. ひき肉と玉ねぎを混ぜます\n3. ハンバーグの形に成形します\n4. フライパンで焼きます\n5. ソースを作ります\n6. ハンバーグにかけて完成です',
    likes: 32,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '合いびき肉', quantity: '400g' },
      { id: 2, name: '玉ねぎ', quantity: '1個' },
      { id: 3, name: 'パン粉', quantity: '1/2カップ' },
      { id: 4, name: '卵', quantity: '1個' },
      { id: 5, name: 'ケチャップ', quantity: '大さじ3' }
    ]
  },
  5: {
    id: 5,
    title: '親子丼',
    genre: '和食',
    servings: 2,
    image: null,
    body: '美味しい親子丼の作り方です。\n\n1. 鶏肉を一口大に切ります\n2. 玉ねぎを薄切りにします\n3. 鍋で鶏肉と玉ねぎを煮ます\n4. 卵を溶きほぐします\n5. 卵を回し入れます\n6. ご飯にのせて完成です',
    likes: 5,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '鶏もも肉', quantity: '200g' },
      { id: 2, name: '卵', quantity: '3個' },
      { id: 3, name: '玉ねぎ', quantity: '1/2個' },
      { id: 4, name: 'ご飯', quantity: '2杯' },
      { id: 5, name: 'だし汁', quantity: '200ml' }
    ]
  },
  6: {
    id: 6,
    title: 'グラタン',
    genre: '洋食',
    servings: 4,
    image: null,
    body: 'クリーミーなグラタンの作り方です。\n\n1. 玉ねぎを薄切りにします\n2. マカロニを茹でます\n3. ホワイトソースを作ります\n4. 具材を混ぜ合わせます\n5. チーズをのせます\n6. オーブンで焼いて完成です',
    likes: 19,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'マカロニ', quantity: '200g' },
      { id: 2, name: '鶏肉', quantity: '150g' },
      { id: 3, name: '玉ねぎ', quantity: '1個' },
      { id: 4, name: 'バター', quantity: '30g' },
      { id: 5, name: 'チーズ', quantity: '100g' }
    ]
  }
}

// レシピデータ
const recipe = ref({})

// お気に入り状態管理用のグローバルストア（一覧ページと同じ）
const favoriteStore = useState('favorites', () => new Set())

// コメントデータ（グローバルストアで管理して永続化）
const commentsStore = useState('comments', () => new Map())

// 現在のレシピのコメント
const comments = computed(() => {
  const recipeComments = commentsStore.value.get(recipeId) || []
  return recipeComments
})

// 表示するコメントを制御
const displayedComments = computed(() => {
  if (showAllComments.value) {
    return [...comments.value].reverse()
  } else {
    return [...comments.value].reverse().slice(0, 3)
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

// ユーザー名の省略処理
const truncateUsername = (username) => {
  if (!username) return 'ユーザー'
  return username.length > 10 ? username.substring(0, 10) + '...' : username
}

// いいねボタンの切り替え
const toggleLike = () => {
  if (!user.value) {
    console.log('⚠️ ログインが必要です')
    return
  }

  recipe.value.isLiked = !recipe.value.isLiked
  
  if (recipe.value.isLiked) {
    // お気に入りに追加
    favoriteStore.value.add(recipe.value.id)
    recipe.value.likes++
    console.log(`💖 ${user.value.displayName || user.value.email} がレシピ${recipe.value.id}「${recipe.value.title}」をお気に入りに追加`)
  } else {
    // お気に入りから削除
    favoriteStore.value.delete(recipe.value.id)
    recipe.value.likes = Math.max(0, recipe.value.likes - 1)
    console.log(`💔 ${user.value.displayName || user.value.email} がレシピ${recipe.value.id}「${recipe.value.title}」をお気に入りから削除`)
  }

  console.log(`現在のお気に入り:`, Array.from(favoriteStore.value))
  // 実際のAPI呼び出し
  // await $fetch(`/api/recipes/${recipe.value.id}/like`, { method: 'POST' })
}

// コメント送信
const submitComment = () => {
  if (!user.value) {
    console.log('⚠️ ログインが必要です')
    return
  }

  if (!newComment.value.trim()) return

  // 現在のレシピのコメント一覧を取得
  const currentComments = commentsStore.value.get(recipeId) || []
  
  const comment = {
    id: Date.now(), // ユニークなIDを生成
    user: { 
      name: user.value.displayName || user.value.email.split('@')[0] || '匿名ユーザー',
      avatar_path: null 
    },
    body: newComment.value,
    createdAt: new Date().toISOString()
  }

  // 新しいコメントを追加
  const updatedComments = [...currentComments, comment]
  commentsStore.value.set(recipeId, updatedComments)
  
  newComment.value = ''

  // 新しいコメントが追加されたらすべて表示する
  if (updatedComments.length > 3) {
    showAllComments.value = true
  }

  console.log('💬 コメント送信:', comment.body)
  console.log('📝 現在のコメント数:', updatedComments.length)
  
  // 実際のAPI呼び出し
  // await $fetch(`/api/recipes/${recipe.value.id}/comments`, {
  //   method: 'POST',
  //   body: { body: comment.body }
  // })
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
  console.log('🔍 レシピ詳細ページの認証チェック開始...')
  
  // Firebase認証の状態確立を待機
  const currentUser = await waitForAuth()
  
  if (!currentUser) {
    console.log('⚠️ 認証失敗 - ログインページにリダイレクト')
    await navigateTo('/auth/login')
    return
  }
  
  console.log('✅ 認証成功:', currentUser.email)

  // レシピデータの取得
  console.log('📖 レシピID:', recipeId)
  
  // モックデータから取得
  const recipeData = recipeDatabase[recipeId]
  if (!recipeData) {
    console.log('❌ レシピが見つかりません')
    await navigateTo('/user')
    return
  }
  
  recipe.value = { ...recipeData }
  
  // お気に入り状態を同期
  recipe.value.isLiked = favoriteStore.value.has(recipe.value.id)
  
  // 初期コメントデータの設定（まだ設定されていない場合のみ）
  if (!commentsStore.value.has(recipeId)) {
    const initialComments = [
      {
        id: 1,
        user: { name: 'ユーザーA', avatar_path: null },
        body: 'めっちゃ美味しかったです！',
        createdAt: new Date('2025-01-01').toISOString()
      },
      {
        id: 2,
        user: { name: 'ユーザーB', avatar_path: null },
        body: '今度作ってみます〜',
        createdAt: new Date('2025-01-02').toISOString()
      },
      {
        id: 3,
        user: { name: 'ユーザーC', avatar_path: null },
        body: '今日の献立に取り入れようと思います。',
        createdAt: new Date('2025-01-03').toISOString()
      },
      {
        id: 4,
        user: { name: 'VeryLongUserNameExample', avatar_path: null },
        body: '材料も揃えやすくて助かります！',
        createdAt: new Date('2025-01-04').toISOString()
      },
      {
        id: 5,
        user: { name: 'CookingLover2024', avatar_path: null },
        body: '家族みんな大絶賛でした！リピート確定です',
        createdAt: new Date('2025-01-05').toISOString()
      },
      {
        id: 6,
        user: { name: 'ママの料理', avatar_path: null },
        body: '子供たちがおかわりしてくれました♪',
        createdAt: new Date('2025-01-06').toISOString()
      },
      {
        id: 7,
        user: { name: 'グルメ太郎', avatar_path: null },
        body: 'プロ級の仕上がりになりました！ありがとうございます',
        createdAt: new Date('2025-01-07').toISOString()
      }
    ]
    commentsStore.value.set(recipeId, initialComments)
  }
  
  console.log('📖 レシピデータ読み込み完了:', recipe.value.title)
  console.log('💖 お気に入り状態:', recipe.value.isLiked)
  console.log('💬 コメント数:', comments.value.length)
  
  // 実際のAPI呼び出し
  // try {
  //   const data = await $fetch(`/api/recipes/${recipeId}`)
  //   recipe.value = data
  // } catch (error) {
  //   console.error('❌ レシピ取得エラー:', error)
  //   await navigateTo('/user')
  // }

  // 初期のtextareaリサイズ
  autoResize()
})
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
    cursor: pointer;
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
        max-width: 250px;
        max-height: 250px;
    }

    .recipe-title-heading {
        font-size: 18px;
    }
}
</style>