<template>
  <div class="admin-login-page">
    <div class="form-container">
      <form class="login-form" @submit.prevent="handleLogin">
        <div class="logo">
          <img src="/images/rabbit-shape.svg" alt="Rabbit Logo" class="logo-image">
        </div>
        <h1 class="login-title">Admin Login</h1>
        <div class="form-group">
          <label class="form-label">メールアドレス</label>
          <input
            type="email"
            class="form-input"
            v-model="form.email"
            :class="{ 'error-input': errors.email }"
            :disabled="loading"
          >
          <div v-if="errors.email" class="error">{{ errors.email }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">パスワード</label>
          <input
            type="password"
            class="form-input"
            v-model="form.password"
            :class="{ 'error-input': errors.password }"
            :disabled="loading"
          >
          <div v-if="errors.password" class="error">{{ errors.password }}</div>
        </div>
        <div v-if="errors.general" class="error general-error">{{ errors.general }}</div>
        <button type="submit" class="submit-button" :disabled="loading">
          {{ loading ? 'ログイン中...' : 'ログイン' }}
        </button>
        <div class="form-footer">
          <NuxtLink to="/auth/forgot-password" class="forgot-link">パスワードを忘れた場合はこちら</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { signInWithEmailAndPassword } from 'firebase/auth'

definePageMeta({
  layout: false
})

const { $auth, $firestore } = useNuxtApp()
const { setAdmin } = useAuth()

const form = ref({
  email: '',
  password: ''
})

const errors = ref({})
const loading = ref(false)


// ログイン処理メソッド
const handleLogin = async () => {
  // バリデーションリセット
  errors.value = {}
  loading.value = true

  // 簡単なバリデーション
  if (!form.value.email) {
    errors.value.email = 'メールアドレスを入力してください'
  }
  if (!form.value.password) {
    errors.value.password = 'パスワードを入力してください'
  }

  // エラーがある場合は送信しない
  if (Object.keys(errors.value).length > 0) {
    loading.value = false
    return
  }

  try {
    // Firebase認証でログイン
    const userCredential = await signInWithEmailAndPassword(
      $auth, 
      form.value.email, 
      form.value.password
    )
    
    const user = userCredential.user
    
    // Firestoreから管理者権限を確認
    const adminDoc = await $firestore.collection('admins').doc(user.uid).get()
    
    if (!adminDoc.exists) {
      // 管理者として登録されていない場合
      await $auth.signOut()
      errors.value.general = '管理者権限がありません'
      loading.value = false
      return
    }

    const adminData = adminDoc.data()
    
    // 管理者情報をセット
    setAdmin({
      uid: user.uid,
      email: user.email,
      displayName: adminData.displayName || user.displayName,
      createdAt: adminData.createdAt
    })

    // ダッシュボードにリダイレクト
    await navigateTo('/admin/dashboard')
    
  } catch (error) {
    console.error('ログインエラー:', error)
    
    // Firebase認証エラーの日本語化
    let errorMessage = 'ログインに失敗しました'
    
    switch (error.code) {
      case 'auth/user-not-found':
        errorMessage = 'ユーザーが見つかりません'
        break
      case 'auth/wrong-password':
        errorMessage = 'パスワードが正しくありません'
        break
      case 'auth/invalid-email':
        errorMessage = 'メールアドレスの形式が正しくありません'
        break
      case 'auth/user-disabled':
        errorMessage = 'このアカウントは無効になっています'
        break
      case 'auth/too-many-requests':
        errorMessage = 'ログイン試行回数が多すぎます。しばらく待ってからお試しください'
        break
      case 'auth/network-request-failed':
        errorMessage = 'ネットワークエラーが発生しました'
        break
      default:
        errorMessage = 'ログインに失敗しました'
    }

    errors.value.general = errorMessage
    loading.value = false
  }
}

// ページ離脱時のクリーンアップ
onUnmounted(() => {
  loading.value = false
})

</script>

<style scoped>
.admin-login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.form-container {
  width: 100%;
  max-width: 400px;
  padding: 20px;
  box-sizing: border-box;
}

.login-form {
  background: white;
  padding: 40px;
  border-radius: 8px;
  text-align: center;
  box-sizing: border-box;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.logo {
  margin-bottom: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.logo-image {
  width: 60px;
  height: 60px;
  object-fit: contain;
}

.login-title {
  font-size: 24px;
  font-weight: normal;
  color: #333;
  margin: 0 0 30px 0;
  font-family: cursive;
  font-style: italic;
}

.form-group {
  margin-bottom: 20px;
  text-align: left;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: normal;
  color: #333;
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  font-size: 16px;
  border: none;
  border-bottom: 1px solid #ddd;
  box-sizing: border-box;
  background: white;
  color: #333;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  background-color: #f8f8f8;
  border-color: #999;
}

.form-input:disabled {
  background-color: #f5f5f5;
  color: #999;
  cursor: not-allowed;
}

.error-input {
  border-color: #dc3545;
}

.error {
  color: #dc3545;
  font-size: 12px;
  margin-top: 4px;
}

.general-error {
  text-align: center;
  margin-bottom: 15px;
  font-size: 14px;
}

.submit-button {
  width: 100%;
  padding: 12px;
  background: #ddd;
  color: #333;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: normal;
  cursor: pointer;
  margin-bottom: 20px;
  box-sizing: border-box;
  transition: all 0.3s ease;
}

.submit-button:hover:not(:disabled) {
  background: #ccc;
}

.submit-button:disabled {
  background: #e0e0e0;
  color: #999;
  cursor: not-allowed;
}

.form-footer {
  text-align: center;
}

.form-footer a {
  display: block;
  color: #888;
  text-decoration: underline;
  font-size: 14px;
  margin-bottom: 10px;
  transition: color 0.3s ease;
}

.form-footer a:hover {
  color: #666;
}

.form-footer a:last-child {
  margin-bottom: 0;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
  .admin-login-page {
    padding: 20px;
  }
  
  .form-container {
    max-width: 100%;
  }
  
  .login-form {
    padding: 30px 20px;
  }
}
</style>