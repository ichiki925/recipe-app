<template>
  <div class="profile-container">
    <h2>プロフィール編集</h2>

    <form @submit.prevent="saveProfile">
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

        <div v-if="fileError" class="file-error-message">
          {{ fileError }}
        </div>
      </div>

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

        <div class="name-counter">
          <span :class="{ 'warning': nameLength > 18, 'error': nameLength > 20 }">
            {{ nameLength }}/20
          </span>
        </div>

        <div v-if="nameError" class="error-message">
          {{ nameError }}
        </div>
      </div>

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
    <div class="danger-zone">
      <button
        @click="deleteAccount"
        class="delete-button"
        :disabled="isDeleting"
      >
        <i v-if="isDeleting" class="fas fa-spinner fa-spin"></i>
        <span v-else>アカウントを削除</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { useHead } from '#app'
import { uploadAvatarToFirebase, deleteAvatarFromFirebase, extractPathFromFirebaseUrl } from '~/utils/firebaseAvatar.js'
import { getAuth as getFirebaseAuth, deleteUser as deleteFirebaseUser } from 'firebase/auth'

definePageMeta({
  ssr: false
})

const { isLoggedIn, initAuth } = useAuth()
const { getAuth, postAuth, putAuth } = useApi()

const avatarUrl = computed(() => {
  if (!user.avatar) return null

  if (user.avatar.startsWith('data:image/')) {
    return user.avatar
  }

  if (user.avatar.startsWith('http://') || user.avatar.startsWith('https://')) {
    return user.avatar
  }

  if (user.avatar.startsWith('/storage/')) {
    const fileName = user.avatar.split('/').pop()
    return `http://localhost/storage/avatars/${fileName}`
  }

  const fileName = user.avatar.includes('/') ? user.avatar.split('/').pop() : user.avatar
  return `http://localhost/storage/avatars/${fileName}`
})

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

const isLoading = ref(false)
const nameError = ref('')
const fileError = ref('')
const isSubmitting = ref(false)
const selectedFile = ref(null)
const isDeleting = ref(false)

const user = reactive({
  id: 1,
  name: '',
  avatar: null,
  firebase_uid: null
})

const nameLength = computed(() => {
  return user.name ? user.name.length : 0
})

const isFormValid = computed(() => {
  return !nameError.value && !fileError.value && user.name.trim().length > 0
})

const validateUserName = (name) => {
  const trimmed = name.trim()

  if (!trimmed) {
    return 'ユーザーネームを入力してください'
  }

  if (trimmed.length > 20) {
    return 'ユーザーネームは20文字以内で入力してください'
  }

  const allowedPattern = /^[\p{L}\p{N}_\-\s]+$/u
  if (!allowedPattern.test(trimmed)) {
    return '使用できない文字が含まれています'
  }

  if (/\s{2,}/.test(trimmed)) {
    return '連続するスペースは使用できません'
  }

  return null
}

const validateFile = (file) => {
  if (!file) return null

  const maxSize = 5 * 1024 * 1024

  if (file.size > maxSize) {
    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2)
    console.error('❌ ファイルサイズ超過:', `${fileSizeMB}MB`)
    return `ファイルサイズは5MB以下にしてください（現在: ${fileSizeMB}MB）`
  }

  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']

  if (!allowedTypes.includes(file.type)) {
    console.error('❌ 無効なファイル形式:', file.type)
    return '対応している形式: JPEG, PNG, GIF, WebP'
  }

  return null
}

const handleNameInput = () => {
  nameError.value = ''

  const validationError = validateUserName(user.name)
  if (validationError) {
    nameError.value = validationError
  }
}

const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  fileError.value = ''
  selectedFile.value = null

  if (!file) {
    return
  }

  const validationError = validateFile(file)
  if (validationError) {
    console.error('❌ バリデーションエラー:', validationError)
    fileError.value = validationError
    event.target.value = ''
    return
  }

  selectedFile.value = file

  const reader = new FileReader()

  reader.onload = (e) => {
    try {
      user.avatar = e.target.result
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
    reader.readAsDataURL(file)
  } catch (error) {
    console.error('❌ FileReader.readAsDataURL エラー:', error)
    fileError.value = 'ファイル処理に失敗しました'
  }
}

const saveProfile = async () => {
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
    let avatarFirebaseUrl = null

    // 新しい画像がアップロードされた場合、Firebase Storageにアップロード
    if (selectedFile.value) {
      try {
        const { $auth } = useNuxtApp()
        const currentUser = $auth.currentUser

        if (!currentUser) {
          throw new Error('認証が必要です')
        }

        console.log('Firebase Storage アップロード開始')
        const uploadResult = await uploadAvatarToFirebase(selectedFile.value, currentUser.uid)
        avatarFirebaseUrl = uploadResult.url
        console.log('Firebase Storage アップロード完了:', avatarFirebaseUrl)

        // 古い画像を削除（Firebase Storage URLの場合のみ）
        if (user.avatar && user.avatar.includes('firebasestorage.googleapis.com')) {
          const oldPath = extractPathFromFirebaseUrl(user.avatar)
          if (oldPath) {
            console.log('古い画像削除開始:', oldPath)
            await deleteAvatarFromFirebase(oldPath)
          }
        }

      } catch (uploadError) {
        console.error('Firebase Storage アップロードエラー:', uploadError)
        fileError.value = '画像のアップロードに失敗しました'
        return
      }
    }

    // Laravel APIに送信するデータを準備
    const updateData = {
      name: user.name.trim()
    }

    // Firebase Storage URLがある場合は追加
    if (avatarFirebaseUrl) {
      updateData.avatar_url = avatarFirebaseUrl
    }

    console.log('プロフィール更新データ送信:', updateData)

    // FormDataではなく、JSONで送信
    const response = await putAuth('user/profile', updateData)

    if (response.data) {
      user.name = response.data.name || user.name
      user.avatar = response.data.avatar_url || user.avatar

      await nextTick()
      const usernameInput = document.getElementById('username')
      if (usernameInput) {
        usernameInput.value = user.name
      }
    }

    // 成功時はファイル選択をクリア
    const avatarInput = document.getElementById('avatar-upload')
    if (avatarInput) {
      avatarInput.value = ''
    }
    selectedFile.value = null

    alert('プロフィールを保存しました！')
    await navigateTo('/user')

  } catch (error) {
    console.error('保存エラー詳細:', error)

    if (error.status === 401) {
      console.error('認証エラー: トークンが無効または期限切れ')
      alert('認証が失効しています。再度ログインしてください。')
      await navigateTo('/auth/login')
      return
    }

    if (error.status === 422 && error.data && error.data.errors) {
      console.error('バリデーションエラー:', error.data.errors)

      if (error.data.errors.name) {
        nameError.value = error.data.errors.name[0]
        console.error('名前エラー:', error.data.errors.name[0])
      }
      if (error.data.errors.avatar) {
        fileError.value = error.data.errors.avatar[0]
        console.error('ファイルエラー:', error.data.errors.avatar[0])
      }

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

const deleteAccount = async () => {
  // 確認ダイアログ（二段階）
  if (!confirm('本当にアカウントを削除しますか？\n\nこの操作は取り消せません。\n- すべてのいいねが削除されます\n- コメントは「削除されたユーザー」として残ります')) {
    return
  }

  if (!confirm('最終確認：アカウントを完全に削除してもよろしいですか？')) {
    return
  }

  isDeleting.value = true

  try {
    const { $auth } = useNuxtApp()
    const currentUser = $auth.currentUser

    if (!currentUser) {
      throw new Error('認証が必要です')
    }

    console.log('アカウント削除開始')

    // 1. Laravel APIでDB削除（いいね削除 + 匿名化）
    await fetch('http://localhost/api/user/profile', {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${await currentUser.getIdToken()}`,
        'Content-Type': 'application/json'
      }
    }).then(async (response) => {
      if (!response.ok) {
        const errorData = await response.json()

        // 管理者エラーの場合
        if (response.status === 403) {
          throw new Error(errorData.message || '管理者アカウントは削除できません')
        }

        throw new Error(errorData.message || 'アカウント削除に失敗しました')
      }
      return response.json()
    })

    console.log('DB削除完了')

    // 2. Firebaseアカウント削除
    const auth = getFirebaseAuth()
    const firebaseUser = auth.currentUser

    if (firebaseUser) {
      await deleteFirebaseUser(firebaseUser)
      console.log('Firebaseアカウント削除完了')
    }

    // 3. ログアウト処理
    alert('アカウントを削除しました')
    await navigateTo('/auth/login')

  } catch (error) {
    console.error('削除エラー:', error)

    if (error.message.includes('管理者')) {
      alert('管理者アカウントは削除できません')
    } else if (error.code === 'auth/requires-recent-login') {
      alert('セキュリティのため、再ログインが必要です。ログインし直してから削除してください。')
      await navigateTo('/auth/login')
    } else {
      alert(error.message || 'アカウント削除に失敗しました')
    }
  } finally {
    isDeleting.value = false
  }
}

onMounted(async () => {
  await initAuth()

  if (!isLoggedIn.value) {
    return navigateTo('/auth/login')
  }

  try {
    const response = await getAuth('user/profile')

    if (response.data) {
      user.id = response.data.id
      user.name = response.data.name || ''
      user.firebase_uid = response.data.firebase_uid

      if (response.data.avatar_url) {
        user.avatar = response.data.avatar_url
        await nextTick()
      } else {
        user.avatar = null
      }
    }
  } catch (error) {
    console.error('❌ プロフィール取得エラー:', error)

    if (error.status === 401) {
      console.error('❌ 認証エラー: ログインが必要です')
      alert('認証エラーが発生しました。再度ログインしてください。')
      await navigateTo('/auth/login')
      return
    }

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
}

.input-wrapper {
  position: relative;
  margin-bottom: 20px;
}

input[type="text"].error {
  border-color: #dc3545;
  box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
}

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

.upload-button.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

.save-button.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.save-button.disabled:hover {
  background: #f0f0f0; /* ホバー効果を無効化 */
}

input[type="text"]:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.fa-spin {
  animation: fa-spin 1s infinite linear;
  margin-right: 5px;
}

.danger-zone {
  margin-top: 40px;
  padding-top: 30px;
  border-top: 1px solid #eee;
}

.delete-button {
  width: 100%;
  padding: 10px;
  background: #fff;
  border: 1px solid #dc3545;
  border-radius: 6px;
  font-weight: bold;
  color: #dc3545;
  cursor: pointer;
  transition: all 0.2s;
}

.delete-button:hover:not(:disabled) {
  background: #dc3545;
  color: #fff;
}

.delete-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}


@keyframes fa-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (max-width: 480px) {
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

  .danger-zone {
    margin-top: 30px;
    padding-top: 20px;
  }
}

@media (max-width: 360px) {
  .profile-container {
      padding: 15px;
  }
}
</style>