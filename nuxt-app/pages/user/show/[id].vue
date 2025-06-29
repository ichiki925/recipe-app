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
              v-for="comment in reversedComments"
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
              <span class="username">{{ comment.user.name }}</span>
              <span class="comment-body">{{ comment.body }}</span>
            </li>
          </ul>

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
  import { useRoute, useHead } from '#app'

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
  const recipeId = route.params.id

  // データ定義
  const newComment = ref('')
  const commentTextarea = ref(null)

  // レシピデータ（実際はAPIから取得）
  const recipe = ref({
    id: 1,
    title: 'ホクホク肉じゃが',
    genre: '和食',
    servings: 4,
    image: null, // 画像がある場合は画像URLを設定
    body: 'ここに作り方の詳細が表示されます。\n\n1. 材料を準備します\n2. 調理を開始します\n3. 完成です',
    likes: 10,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '玉ねぎ', quantity: '1個' },
      { id: 2, name: '人参', quantity: '1本' },
      { id: 3, name: '豚肉', quantity: '200g' },
      { id: 4, name: '醤油', quantity: '大さじ2' }
    ]
  })

  // コメントデータ
  const comments = ref([
    {
      id: 1,
      user: { name: 'ユーザーA', avatar_path: null },
      body: 'めっちゃ美味しかったです！'
    },
    {
      id: 2,
      user: { name: 'ユーザーB', avatar_path: null },
      body: '今度作ってみます〜'
    }
  ])

  // コメントを逆順で表示
  const reversedComments = computed(() => {
    return [...comments.value].reverse()
  })

  // いいねボタンの切り替え
  const toggleLike = () => {
    recipe.value.isLiked = !recipe.value.isLiked
    if (recipe.value.isLiked) {
      recipe.value.likes++
    } else {
      recipe.value.likes--
    }

    console.log(`レシピ${recipe.value.id}をいいね: ${recipe.value.isLiked}`)
    // 実際のAPI呼び出し
    // await $fetch(`/api/recipes/${recipe.value.id}/like`, { method: 'POST' })
  }

  // コメント送信
  const submitComment = () => {
    if (!newComment.value.trim()) return

    const comment = {
      id: comments.value.length + 1,
      user: { name: '現在のユーザー', avatar_path: null },
      body: newComment.value
    }

    comments.value.push(comment)
    newComment.value = ''

    console.log('コメント送信:', comment.body)
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
    // レシピデータの取得
    console.log('レシピID:', recipeId)
    // 実際のAPI呼び出し
    // const data = await $fetch(`/api/recipes/${recipeId}`)
    // recipe.value = data

    // 初期のtextareaリサイズ
    autoResize()
  })
  </script>

  <style scoped>
  @import "@/assets/css/common.css";

  body {
    margin: 0;
    font-family: sans-serif;
}

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

/* 画像下のアクションエリア */
.image-actions {
    margin-top: 30px;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 50px;
}

.image-actions button {
    padding: 10px;
    border: none;
    border-radius: 6px;
    background-color: #eee;
    cursor: pointer;
    font-weight: bold;
}

.image-actions button:hover {
    background-color: #ddd;
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
    border: 1px solid #ccc;
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
}

.comment-body {
    flex: 1;
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

.recipe-title,
.recipe-form textarea {
    width: 100%;
    padding: 10px;
    border: none;
    box-sizing: border-box;
    font-size: 14px;
}

.form {
    margin-top: 100px;
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

/* 詳細ページ専用スタイル（CSS競合を避けるため最小限に） */
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

/* スマホ用 (480px以下) */
@media (max-width: 480px) {
    .recipe-create-container {
        padding: 15px;
        gap: 20px;
    }

    .left-column {
        gap: 15px;
    }

    .image-preview {
        max-width: 250px;
        max-height: 250px;
    }

    .recipe-title-heading {
        font-size: 16px;
        margin-bottom: 5px;
    }

    .recipe-form label {
        margin-top: 20px;
        margin-bottom: 8px;
    }

    #comment-box {
        padding: 8px 40px 8px 8px;
        font-size: 13px;
    }

    .send-button {
        right: 8px;
        bottom: 8px;
        font-size: 12px;
    }

    .comment-item {
        margin-bottom: 8px;
    }

    .comment-avatar-icon,
    .comment-avatar {
        width: 28px;
        height: 28px;
        font-size: 14px;
        margin: 6px;
    }

    .username {
        font-size: 9px;
    }

    .comment-body {
        font-size: 13px;
    }

    .icon-button {
        padding-right: 10px;
    }

    .heart-icon-filled,
    .heart-icon-outline {
        font-size: 16px !important;
    }

    .like-count {
        font-size: 9px;
    }

    .ingredient-name,
    .ingredient-qty {
        padding: 8px;
        font-size: 13px;
    }
}

/* 超小画面用 (320px以下) */
@media (max-width: 320px) {
    .recipe-create-container {
        padding: 10px;
    }

    .image-preview {
        max-width: 230px;
        max-height: 230px;
    }

    .recipe-title-heading {
        font-size: 15px;
    }

    #comment-box {
        padding: 6px 35px 6px 6px;
        font-size: 12px;
    }

    .send-button {
        right: 6px;
        bottom: 6px;
        font-size: 11px;
    }

    .comment-avatar-icon,
    .comment-avatar {
        width: 24px;
        height: 24px;
        font-size: 12px;
        margin: 4px;
    }

    .ingredient-name,
    .ingredient-qty {
        padding: 6px;
        font-size: 12px;
    }
}

  </style>