<template>
  <div class="profile-container">
    <h2>プロフィール編集</h2>

    <form @submit.prevent="saveProfile">
      <!-- アイコン -->
      <div class="avatar-section">
        <img
          v-if="avatarUrl"
          :src="avatarUrl"
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
          :key="user.id || 'default'"
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
import { useHead, useRuntimeConfig } from '#app'
import { onAuthStateChanged } from 'firebase/auth'


// 認証関連
const { user: authUser, isLoggedIn, getIdToken } = useAuth()
const { $auth } = useNuxtApp()
const config = useRuntimeConfig()


const avatarUrl = computed(() => {
  if (!user.avatar) return null

  if (user.avatar.startsWith('data:image/')) {
    return user.avatar
  }
  
  // ✅ サーバーからの画像URL（既存画像）
  if (user.avatar.startsWith('http://') || user.avatar.startsWith('https://')) {
    return user.avatar
  }
  
  // ✅ 相対パス（サーバーからの画像）
  if (user.avatar.startsWith('/storage/')) {
    const fileName = user.avatar.split('/').pop()
    return `http://localhost/storage/avatars/${fileName}`
  }
  
  // フォールバック
  const fileName = user.avatar.includes('/') ? user.avatar.split('/').pop() : user.avatar
  return `http://localhost/storage/avatars/${fileName}`
})


// デバッグモード（開発環境でのみ有効）
const debugMode = ref(process.env.NODE_ENV === 'development')
const tokenStatus = ref('未確認')

// 必要な認証関数を直接定義
const getCurrentUser = () => $auth.currentUser

const waitForAuth = () => {
  return new Promise((resolve) => {
    if ($auth.currentUser) {
      resolve($auth.currentUser)
    } else {
      const unsubscribe = onAuthStateChanged($auth, (user) => {
        unsubscribe()
        resolve(user)
      })
    }
  })
}


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

  if (trimmed.length > 20) {
    return 'ユーザーネームは20文字以内で入力してください'
  }

  // 使用可能文字のチェック（日本語、英数字、一部記号）
  const allowedPattern = /^[\p{L}\p{N}_\-\s]+$/u
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
  console.log('🔍 ファイルバリデーション開始:', file.name)
  
  if (!file) {
    console.log('❌ ファイルなし')
    return null
  }

  // ファイルサイズチェック（5MB制限）
  const maxSize = 5 * 1024 * 1024 // 5MB
  console.log('📏 サイズチェック:', {
    'file_size': file.size,
    'max_size': maxSize,
    'is_over': file.size > maxSize
  })
  
  if (file.size > maxSize) {
    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2)
    console.error('❌ ファイルサイズ超過:', `${fileSizeMB}MB`)
    return `ファイルサイズは5MB以下にしてください（現在: ${fileSizeMB}MB）`
  }

  // ファイル形式チェック
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
  console.log('🔍 形式チェック:', {
    'file_type': file.type,
    'allowed_types': allowedTypes,
    'is_allowed': allowedTypes.includes(file.type)
  })
  
  if (!allowedTypes.includes(file.type)) {
    console.error('❌ 無効なファイル形式:', file.type)
    return '対応している形式: JPEG, PNG, GIF, WebP'
  }

  console.log('✅ バリデーション通過:', file.name)
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
  console.log('🖼️ ファイル選択イベント開始')

  const file = event.target.files[0]
  fileError.value = ''

  // ✅ ファイル情報の詳細ログ
  console.log('📁 選択されたファイル詳細:', {
    'file': file,
    'name': file?.name,
    'size': file?.size,
    'type': file?.type,
    'lastModified': file?.lastModified
  })


  if (!file) {
    console.log('⚠️ ファイルが選択されていません')
    return
  }

  // ✅ ファイルサイズの詳細チェック
  const fileSizeKB = (file.size / 1024).toFixed(2)
  const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2)
  
  console.log('📊 ファイルサイズ詳細:', {
    'bytes': file.size,
    'KB': fileSizeKB,
    'MB': fileSizeMB,
    'limit_5MB': 5 * 1024 * 1024,
    'is_over_limit': file.size > (5 * 1024 * 1024)
  })


  // ファイルバリデーション
  const validationError = validateFile(file)
  if (validationError) {
    console.error('❌ バリデーションエラー:', validationError)
    fileError.value = validationError
    event.target.value = '' // ファイル選択をリセット
    return
  }

  console.log('✅ バリデーション通過')

  // プレビュー用にファイルを読み込み
  const reader = new FileReader()

  reader.onload = (e) => {
    try {
      console.log('📖 FileReader読み込み成功')
      user.avatar = e.target.result // プレビュー表示用
      
      // ✅ 強制的にリアクティブ更新をトリガー
      nextTick(() => {
        console.log('🔄 nextTick後のavatarUrl:', avatarUrl.value)
      })
      
      console.log('✅ プレビュー設定完了:', {
        'data_url_length': e.target.result?.length,
        'starts_with': e.target.result?.substring(0, 50)
      })
    } catch (error) {
      console.error('❌ FileReader onload エラー:', error)
      fileError.value = 'プレビュー表示に失敗しました'
    }
  }
  
  reader.onerror = (error) => {
    console.error('❌ FileReader エラー:', error)
    fileError.value = 'ファイル読み込みに失敗しました'
  }
  
  reader.onabort = () => {
    console.warn('⚠️ FileReader が中断されました')
  }

  try {
    console.log('📖 FileReader.readAsDataURL 開始')
    reader.readAsDataURL(file)
  } catch (error) {
    console.error('❌ FileReader.readAsDataURL エラー:', error)
    fileError.value = 'ファイル処理に失敗しました'
  }

  console.log('🎯 handleAvatarChange 完了:', {
    'file_name': file.name,
    'file_size_kb': fileSizeKB
  })
}




// プロフィール保存処理（バリデーション付き）
// プロフィール保存処理（FormData改善版）
const saveProfile = async () => {
  console.log('🚀 プロフィール保存処理開始')
  
  // ✅ 送信前の詳細確認
  console.log('送信前の詳細確認:', {
    'user.name': user.name,
    'user.name.trim()': user.name.trim(),
    'user.name.length': user.name.length,
    'isEmpty': user.name.trim() === '',
    'typeof': typeof user.name
  })

  // 最終バリデーション
  const nameValidationError = validateUserName(user.name)
  if (nameValidationError) {
    nameError.value = nameValidationError
    console.error('❌ バリデーションエラー:', nameValidationError)
    return
  }

  if (isSubmitting.value) return
  isSubmitting.value = true
  isLoading.value = true

  try {
    const currentUser = await waitForAuth()
    if (!currentUser) {
      alert('認証エラーが発生しました。再度ログインしてください。')
      await navigateTo('/auth/login')
      return
    }

    const token = await getIdToken()

    // ✅ FormData作成（改善版）
    const formData = new FormData()
    
    // 🔧 重要: 名前の確実な追加
    const trimmedName = user.name.trim()
    if (trimmedName) {
      formData.append('name', trimmedName)
      console.log('✅ 名前をFormDataに追加:', trimmedName)
    } else {
      console.error('❌ 名前が空です')
      nameError.value = 'ユーザーネームを入力してください'
      return
    }

    // アバター画像の処理
    const avatarInput = document.getElementById('avatar-upload')
    let hasNewAvatar = false
    
    if (avatarInput && avatarInput.files && avatarInput.files[0]) {
      formData.append('avatar', avatarInput.files[0])
      hasNewAvatar = true
      console.log('✅ アバター画像をFormDataに追加:', {
        name: avatarInput.files[0].name,
        size: (avatarInput.files[0].size / 1024).toFixed(1) + 'KB',
        type: avatarInput.files[0].type
      })
    }

    // ✅ FormDataの内容を確認（デバッグ用）
    console.log('📦 FormData の内容確認:')
    for (let pair of formData.entries()) {
      if (pair[1] instanceof File) {
        console.log(`  ${pair[0]}: [File] ${pair[1].name} (${(pair[1].size / 1024).toFixed(1)}KB)`)
      } else {
        console.log(`  ${pair[0]}: ${pair[1]}`)
      }
    }

    // ✅ リクエスト送信前の最終確認
    console.log('📡 API送信情報:', {
      url: config.public.apiBaseUrl + '/user/profile',
      method: 'PUT',
      hasToken: !!token,
      tokenPreview: token ? token.substring(0, 30) + '...' : 'なし'
    })

    formData.append('_method', 'PUT')

    // APIリクエスト
    const response = await $fetch('/user/profile', {
      baseURL: config.public.apiBaseUrl,
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
      body: formData,
      credentials: 'omit',
    })

    console.log('✅ プロフィール更新成功:', response)

    // レスポンスデータで確実に更新
    if (response.data) {
      console.log('📦 サーバーからのレスポンスデータ:', {
        id: response.data.id,
        name: response.data.name,
        avatar_url: response.data.avatar_url
      })

      // 🔧 重要: サーバーから返された値で更新
      user.name = response.data.name || user.name
      user.avatar = response.data.avatar_url || user.avatar
      
      console.log('✅ ローカルデータ更新完了:', {
        name: user.name,
        avatar: user.avatar
      })
      
      // DOM要素も更新
      await nextTick()
      const usernameInput = document.getElementById('username')
      if (usernameInput) {
        usernameInput.value = user.name
        console.log('🔧 入力フィールド更新完了:', user.name)
      }
    }

    // ファイル入力をクリア（新しい画像がアップロードされた場合のみ）
    if (hasNewAvatar && avatarInput) {
      avatarInput.value = ''
      console.log('🔧 ファイル入力をクリア')
    }

    alert('プロフィールを保存しました！')
    console.log('📱 レシピ一覧ページへ遷移')
    await navigateTo('/user')

  } catch (error) {
    console.error('❌ 保存エラー詳細:', {
      status: error.status,
      statusText: error.statusText,
      data: error.data,
      message: error.message
    })

    if (error.status === 401) {
      console.error('❌ 認証エラー: トークンが無効または期限切れ')
      alert('認証が失効しています。再度ログインしてください。')
      await navigateTo('/auth/login')
      return
    }

    // Laravel側のバリデーションエラーを処理
    if (error.status === 422 && error.data && error.data.errors) {
      console.error('❌ バリデーションエラー:', error.data.errors)
      
      if (error.data.errors.name) {
        nameError.value = error.data.errors.name[0]
        console.error('名前エラー:', error.data.errors.name[0])
      }
      if (error.data.errors.avatar) {
        fileError.value = error.data.errors.avatar[0]
        console.error('ファイルエラー:', error.data.errors.avatar[0])
      }
      
      // エラーメッセージも表示
      const errorMessage = error.data.message || 'バリデーションエラーが発生しました'
      alert(errorMessage)
    } else {
      const errorMessage = error.data?.message || error.message || '保存に失敗しました'
      alert(errorMessage)
    }
  } finally {
    isLoading.value = false
    isSubmitting.value = false
  }
}


// ページ読み込み時にユーザーデータを取得
onMounted(async () => {
  console.log('🔍 プロフィールページ読み込み')
  
  try {
    const currentUser = await waitForAuth()

    if (!currentUser) {
      console.log('⚠️ 認証失敗 - ログインページにリダイレクト')
      await navigateTo('/auth/login')
      return
    }

    console.log('✅ 認証確認完了:', currentUser.uid)

    const token = await getIdToken()
    tokenStatus.value = token ? 'トークンあり' : 'トークンなし'
    
    console.log('🔑 取得したトークン（最初の50文字）:', token ? token.substring(0, 50) + '...' : 'なし')
    console.log('🌐 API URL:', config.public.apiBaseUrl)
    console.log('📡 APIリクエスト送信中...')

    const response = await $fetch('/user/profile', {
      baseURL: config.public.apiBaseUrl,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      onRequestError({ request, options, error }) {
        console.error('❌ プロフィール取得リクエストエラー:', error)
        console.error('❌ リクエスト URL:', request)
        console.error('❌ リクエスト Headers:', options.headers)
      },
      onResponseError({ request, response, options }) {
        console.error('❌ プロフィール取得レスポンスエラー:', {
          url: request,
          status: response.status,
          statusText: response.statusText,
          body: response._data,
          headers: response.headers
        })
      }
    })
    
    console.log('📦 APIレスポンス:', response)

    if (response.data) {
      user.id = response.data.id
      user.name = response.data.name || ''

      // ✅ 重要：アバター画像の設定をデバッグ強化
      console.log('🔍 アバター情報詳細:', {
        'response.data.avatar_url': response.data.avatar_url,
        'typeof avatar_url': typeof response.data.avatar_url,
        'avatar_url length': response.data.avatar_url?.length
      })

      if (response.data.avatar_url) {
        user.avatar = response.data.avatar_url
        console.log('✅ アバター設定完了:', user.avatar)
        
        // 即座にcomputed値も確認
        await nextTick()
        console.log('✅ computed avatarUrl:', avatarUrl.value)

        // 画像URLの直接テスト
        const testUrl = avatarUrl.value
        console.log('🧪 画像URL直接テスト:', testUrl)

      } else {
        user.avatar = null
        console.log('ℹ️ アバターなし - デフォルトアイコン使用')
      }
    }
  } catch (error) {
    console.error('❌ プロフィール取得エラー:', error)
    console.error('❌ エラーの詳細:', {
      status: error.status,
      statusText: error.statusText,
      data: error.data,
      message: error.message
    })
    
    if (error.status === 401) {
      console.error('❌ 認証エラー: ログインが必要です')
      alert(`認証エラーが発生しました。詳細: ${JSON.stringify(error.data)}`)
      await navigateTo('/auth/login')
      return
    }
    
    // ✅ エラー時もデフォルト値を設定
    user.name = ''
    user.avatar = null
  }
})


</script>

<style scoped>
@import "@/assets/css/common.css";

.profile-container {
    width: 400px;
    margin: 130px auto 50px;
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