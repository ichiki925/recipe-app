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
                class="fas fa-user avatar-icon"
              ></i>
              <img 
                v-else
                :src="comment.user.avatar_path" 
                class="avatar-img" 
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
  
  <style>
  @import "@/assets/css/common.css";
  @import "@/assets/css/user/show.css";
  
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
  </style>