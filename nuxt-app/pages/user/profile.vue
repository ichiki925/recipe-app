<template>
  <div class="profile-container">
    <h2>プロフィール編集</h2>

    <form @submit.prevent="saveProfile">
      <!-- アイコン -->
      <div class="avatar-section">
        <img 
          v-if="user.avatar" 
          :src="user.avatar" 
          alt="アイコン" 
          class="avatar-img"
        >
        <div 
          v-else 
          class="avatar-icon"
        >
          <i class="fas fa-user material-symbols-outlined"></i>
        </div>
        
        <label for="avatar-upload" class="file-upload-label">
          <input 
            id="avatar-upload"
            type="file" 
            name="avatar" 
            accept="image/*"
            @change="handleAvatarChange"
            :disabled="isSubmitting"
            style="display: none;"
          >
          <span class="upload-button" :class="{ 'disabled': isSubmitting }">
            画像を選択
          </span>
        </label>

        <!-- ファイルエラーメッセージ -->
        <div v-if="fileError" class="file-error-message">
          {{ fileError }}
        </div>
      </div>

      <!-- 名前 -->
      <div class="input-wrapper">
        <label for="username">ユーザーネーム</label>
        <input 
          id="username"
          type="text" 
          name="name" 
          v-model="user.name"
          :class="{ 'error': nameError }"
          @input="handleNameInput"
          :disabled="isSubmitting"
          maxlength="20"
          required
        >

        <!-- 文字数カウンター -->
        <div class="name-counter">
          <span :class="{ 'warning': nameLength > 18, 'error': nameLength > 20 }">
            {{ nameLength }}/20
          </span>
        </div>
        
        <!-- エラーメッセージ -->
        <div v-if="nameError" class="error-message">
          {{ nameError }}
        </div>
      </div>

      <!-- 保存ボタン -->
      <button 
        type="submit" 
        class="save-button" 
        :class="{ 'disabled': !isFormValid || isSubmitting }"
        :disabled="!isFormValid || isSubmitting"
      >
        <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
        <span v-else>{{ isLoading ? '保存中...' : '保存する' }}</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useHead } from '#app'

// Head設定
useHead({
  title: 'プロフィール編集',
  link: [
    {
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap'
    },
    {
      rel: 'stylesheet',
      href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'
    }
  ]
})

// データ定義
const isLoading = ref(false)

// ⭐ バリデーション関連のリアクティブ変数
const nameError = ref('')
const fileError = ref('')
const isSubmitting = ref(false)

// ユーザーデータ（実際はAPIから取得）
const user = reactive({
  id: 1,
  name: '',
  avatar: null // 画像がある場合は画像URLを設定
})

// ⭐ 文字数計算
const nameLength = computed(() => {
  return user.name ? user.name.length : 0
})

// ⭐ フォーム全体のバリデーション状態
const isFormValid = computed(() => {
  return !nameError.value && !fileError.value && user.name.trim().length > 0
})

// ⭐ ユーザーネームバリデーション関数
const validateUserName = (name) => {
  const trimmed = name.trim()
  
  if (!trimmed) {
    return 'ユーザーネームを入力してください'
  }
  
  if (trimmed.length < 2) {
    return 'ユーザーネームは2文字以上で入力してください'
  }
  
  if (trimmed.length > 20) {
    return 'ユーザーネームは20文字以内で入力してください'
  }
  
  // 使用可能文字のチェック（日本語、英数字、一部記号）
  const allowedPattern = /^[a-zA-Z0-9\u3040-\u309F\u30A0-\u30FF\u4E00-\u9FAF_\-\s]+$/
  if (!allowedPattern.test(trimmed)) {
    return '使用できない文字が含まれています'
  }
  
  // 連続するスペースのチェック
  if (/\s{2,}/.test(trimmed)) {
    return '連続するスペースは使用できません'
  }
  
  return null // バリデーション通過
}

// ⭐ ファイルバリデーション関数
const validateFile = (file) => {
  if (!file) return null
  
  // ファイルサイズチェック（5MB制限）
  const maxSize = 5 * 1024 * 1024 // 5MB
  if (file.size > maxSize) {
    return 'ファイルサイズは5MB以下にしてください'
  }
  
  // ファイル形式チェック
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    return '対応している形式: JPEG, PNG, GIF, WebP'
  }
  
  return null // バリデーション通過
}

// ⭐ リアルタイムユーザーネームバリデーション
const handleNameInput = () => {
  nameError.value = ''
  
  // リアルタイムバリデーション
  const validationError = validateUserName(user.name)
  if (validationError) {
    nameError.value = validationError
  }
}

// アバター画像の変更処理（バリデーション付き）
const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  fileError.value = ''
  
  if (!file) return
  
  // ファイルバリデーション
  const validationError = validateFile(file)
  if (validationError) {
    fileError.value = validationError
    event.target.value = '' // ファイル選択をリセット
    return
  }
  
  // プレビュー用にファイルを読み込み
  const reader = new FileReader()
  reader.onload = (e) => {
    user.avatar = e.target.result
  }
  reader.readAsDataURL(file)
  
  console.log('選択されたファイル:', file.name, `(${(file.size / 1024).toFixed(1)}KB)`)
}

// プロフィール保存処理（バリデーション付き）
const saveProfile = async () => {
  // 最終バリデーション
  const nameValidationError = validateUserName(user.name)
  if (nameValidationError) {
    nameError.value = nameValidationError
    return
  }
  
  // 送信中の重複防止
  if (isSubmitting.value) return
  isSubmitting.value = true
  isLoading.value = true
  
  try {
    console.log('プロフィール保存:', {
      name: user.name.trim(),
      avatar: user.avatar ? '画像あり' : '画像なし'
    })
    
    // 実際のAPI呼び出し
    // const response = await $fetch('/api/profile', {
    //   method: 'PUT',
    //   body: {
    //     name: user.name.trim(),
    //     avatar: avatarFile
    //   }
    // })
    
    // 保存成功の処理
    await new Promise(resolve => setTimeout(resolve, 1000)) // デモ用の遅延
    alert('プロフィールを保存しました！')
    
    // エラーをクリア
    nameError.value = ''
    fileError.value = ''
    
  } catch (error) {
    console.error('保存エラー:', error)
    alert('保存に失敗しました')
  } finally {
    isLoading.value = false
    isSubmitting.value = false
  }
}

// ページ読み込み時にユーザーデータを取得
onMounted(async () => {
  console.log('プロフィールページ読み込み')
  // 実際のAPI呼び出し
  // const userData = await $fetch('/api/user/profile')
  // Object.assign(user, userData)
})
</script>

<style scoped>
@import "@/assets/css/common.css";

.profile-container {
    width: 400px;
    margin: 50px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.profile-container h2 {
    text-align: center;
    margin-bottom: 20px;
    font-weight: lighter;
    font-size: 20px;
}

.avatar-section {
    text-align: center;
    margin-bottom: 20px;
}

.avatar-img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ccc;
}

.no-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #eee;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    font-size: 48px;
    color: #aaa;
}

.avatar-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-color: #eee;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
}

.upload-button {
    display: inline-block;
    padding: 8px 16px;
    background-color: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
    color: #606060;
    margin: 10px;
}

.upload-button:hover {
    background-color: #e0e0e0;
}


label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
    color: #606060;
}

input[type="file"] {
    padding: 10px 16px;
    font-size: 14px;
    border-radius: 6px;
    cursor: pointer;
    color: #ccc;

}

input[type="text"] {
    width: 100%;
    padding: 12px;
    margin-top: 5px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
}

input[type="text"]:focus {
    outline: none;
}

.save-button {
    width: 100%;
    margin-top: 30px;
    padding: 10px;
    background: #f0f0f0;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    color: #606060;
    cursor: pointer;
}

.save-button:hover:not(:disabled) {
    background: #e0e0e0;
    /* ホバー時に少し濃く */
}

/* ⭐ 以下を既存CSSの後に追加 */

/* 入力フィールドのラッパー */
.input-wrapper {
    position: relative;
    margin-bottom: 20px;
}

/* エラー状態の入力フィールド */
input[type="text"].error {
    border-color: #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
}

/* 文字数カウンター */
.name-counter {
    position: absolute;
    right: 10px;
    top: 35px;
    font-size: 10px;
    color: #666;
    pointer-events: none;
}

.name-counter .warning {
    color: #ffc107;
}

.name-counter .error {
    color: #dc3545;
    font-weight: bold;
}

/* エラーメッセージ */
.error-message {
    position: absolute;
    bottom: -18px;
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

/* ファイルエラーメッセージ */
.file-error-message {
    margin-top: 8px;
    font-size: 11px;
    color: #dc3545;
    text-align: center;
    background-color: #fff3f3;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #ffc1c1;
}

/* アップロードボタンの無効状態 */
.upload-button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* 保存ボタンの無効状態 */
.save-button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.save-button.disabled:hover {
    background: #f0f0f0; /* ホバー効果を無効化 */
}

/* 入力フィールドの無効状態 */
input[type="text"]:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

/* スピナーアニメーション */
.fa-spin {
    animation: fa-spin 1s infinite linear;
    margin-right: 5px;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}


@media (max-width: 480px) {

    /* 全体の背景を白に */
    body {
        background-color: #fff;
    }

    .profile-container {
        width: 100%;
        margin: 0;
        padding: 20px;
        /* ボックススタイルを削除 */
        background: transparent;
        border-radius: 0;
        box-shadow: none;
    }

    .avatar-img,
    .avatar-icon {
        width: 100px;
        height: 100px;
    }

    .avatar-icon {
        font-size: 40px;
    }

    .material-symbols-outlined {
        font-size: 40px;
    }

    .profile-container h2 {
        font-size: 18px;
    }

    .upload-button {
        padding: 6px 12px;
        font-size: 12px;
    }

    input[type="text"] {
        padding: 12px;
        font-size: 16px;
    }

    .name-counter {
        position: static;
        text-align: right;
        margin-top: 2px;
        margin-bottom: 5px;
    }
    
    .error-message {
        position: static;
        margin-top: 5px;
        font-size: 12px;
    }
    
    .file-error-message {
        font-size: 12px;
    }

    .save-button {
        padding: 12px;
        font-size: 16px;
    }
}

@media (max-width: 360px) {
    .profile-container {
        padding: 15px;
    }
}


</style>