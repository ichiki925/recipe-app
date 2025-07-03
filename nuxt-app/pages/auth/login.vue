<template>
  <div class="login-page">
    <div class="form-container">
      <form class="login-form" @submit.prevent="handleLogin">
        <div class="logo">
          <img src="/images/rabbit-shape.svg" alt="Rabbit Logo" class="logo-image">
        </div>
        <h1 class="login-title">Login</h1>

        <!-- 登録完了メッセージ -->
        <div v-if="registeredMessage" class="success-message">
          {{ registeredMessage }}
        </div>

        <!-- 一般エラーメッセージ -->
        <div v-if="errors.general" class="error-message">
          {{ errors.general }}
        </div>

        <!-- 成功メッセージ -->
        <div v-if="successMessage" class="success-message">
          {{ successMessage }}
        </div>

        <div class="form-group">
          <label class="form-label">メールアドレス</label>
          <input
            type="email"
            class="form-input"
            v-model="form.email"
            :class="{ 'error-input': errors.email }"
            required
            autocomplete="email"
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
            required
            autocomplete="current-password"
          >
          <div v-if="errors.password" class="error">{{ errors.password }}</div>
        </div>

        <button type="submit" class="submit-button" :disabled="loading">
          {{ loading ? 'ログイン中...' : 'ログイン' }}
        </button>

        <div class="form-footer">
          <NuxtLink to="/auth/forgot-password">パスワードを忘れた方はこちら</NuxtLink>
        </div>
        <div class="form-footer">
          <NuxtLink to="/auth/register">アカウントをお持ちでない方はこちら</NuxtLink>
        </div>
        <div class="form-footer">
          <NuxtLink to="/">トップページに戻る</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false
})

// リアクティブデータ
const form = reactive({
  email: '',
  password: ''
})

const errors = ref({})
const loading = ref(false)
const successMessage = ref('')
const registeredMessage = ref('')

// useAuth composableを使用
const { login } = useAuth()

// URLクエリパラメータをチェック
const route = useRoute()

// コンポーネントマウント時の処理
onMounted(() => {
  // 登録完了時のメッセージ表示
  if (route.query.registered === 'true') {
    registeredMessage.value = '会員登録が完了しました。ログインしてください。'

    // 3秒後にメッセージを非表示
    setTimeout(() => {
      registeredMessage.value = ''
    }, 3000)
  }
})

const handleLogin = async () => {
  try {
    loading.value = true
    errors.value = {}
    successMessage.value = ''

    console.log('🚀 ログイン開始:', form.email)

    // バリデーション
    if (!validateForm()) {
      console.log('❌ バリデーションエラー')
      return
    }

    // useAuth が利用可能かチェック
    if (!login) {
      console.error('❌ useAuth composable が利用できません')
      errors.value.general = 'システムエラーが発生しました'
      return
    }

    // Firebase認証でログイン
    console.log('🔐 Firebase認証実行中...')
    const result = await login(form.email, form.password)

    if (!result || !result.user) {
      console.error('❌ ログイン結果が無効です:', result)
      errors.value.general = 'ログインに失敗しました'
      return
    }

    console.log('✅ ログイン成功:', result.user.uid)
    console.log('👤 ユーザー情報:', {
      uid: result.user.uid,
      email: result.user.email,
      displayName: result.user.displayName
    })

    successMessage.value = 'ログインに成功しました！'

    // Firebase認証状態の確立を待機
    console.log('⏳ 認証状態の確立を待機中...')
    await new Promise(resolve => setTimeout(resolve, 500))

    // 少し待ってからリダイレクト
    console.log('🔄 /user ページにリダイレクト中...')
    setTimeout(async () => {
      try {
        await navigateTo('/user', { replace: true })
      } catch (navError) {
        console.error('❌ ナビゲーションエラー:', navError)
        // 手動でページ遷移
        window.location.href = '/user'
      }
    }, 200)

  } catch (error) {
    console.error('❌ ログインエラー:', error)
    console.error('エラーの詳細:', {
      message: error.message,
      code: error.code,
      stack: error.stack
    })

    // エラーメッセージの日本語化
    let errorMessage = 'ログインに失敗しました'

    if (error.code) {
      switch (error.code) {
        case 'auth/user-not-found':
          errorMessage = 'このメールアドレスは登録されていません'
          break
        case 'auth/wrong-password':
          errorMessage = 'パスワードが間違っています'
          break
        case 'auth/invalid-email':
          errorMessage = 'メールアドレスの形式が正しくありません'
          break
        case 'auth/too-many-requests':
          errorMessage = 'ログイン試行回数が多すぎます。しばらく待ってから再試行してください'
          break
        case 'auth/network-request-failed':
          errorMessage = 'ネットワークエラーが発生しました'
          break
        case 'auth/user-disabled':
          errorMessage = 'このアカウントは無効化されています'
          break
        case 'auth/invalid-credential':
          errorMessage = 'メールアドレスまたはパスワードが間違っています'
          break
        default:
          errorMessage = error.message || 'ログインに失敗しました'
      }
    } else {
      errorMessage = error.message || 'システムエラーが発生しました'
    }

    errors.value.general = errorMessage

  } finally {
    loading.value = false
  }
}

// バリデーション関数
const validateForm = () => {
  let isValid = true

  // メールアドレスチェック
  if (!form.email.trim()) {
    errors.value.email = 'メールアドレスを入力してください'
    isValid = false
  } else if (!/\S+@\S+\.\S+/.test(form.email)) {
    errors.value.email = '正しいメールアドレスを入力してください'
    isValid = false
  }

  // パスワードチェック
  if (!form.password) {
    errors.value.password = 'パスワードを入力してください'
    isValid = false
  }

  return isValid
}
</script>

<style scoped>
.login-page {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;

    background-color: #f2f2f2;
    font-family: 'Noto Sans JP', sans-serif;
    color: #555;
    font-weight: 300;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0;
    padding: 0;
}

.form-container {
    max-width: 400px;
    width: 90%;
    padding: 2rem;
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.login-form {
    text-align: center;
}

.logo {
    text-align: center;
    margin-bottom: 1rem;
    width: 100%;
}

.logo-image {
    width: 60px;
    height: auto;
    opacity: 0.9;
    display: block;
    margin: 0 auto;
}

.login-title {
    color: #222;
    font-size: 1.8rem;
    font-family: cursive;
    margin-bottom: 2rem;
    margin-top: 0.5rem;
    font-weight: 300;
    text-align: center;
}

.form-group {
    margin-bottom: 1.5rem;
    text-align: left;
}

.form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.95rem;
    color: #333;
    font-weight: 400;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: none;
    border-bottom: 1px solid #dcdcdc;
    background-color: #fff;
    font-size: 1rem;
    font-weight: 400;
    color: #555;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    background-color: #f8f8f8;
    border-bottom-color: #555;
}

.form-input.error-input {
    border-bottom-color: #d9534f;
}

.error {
    font-size: 0.85rem;
    color: #d9534f;
    margin-top: 0.3rem;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 0.75rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    border: 1px solid #f5c6cb;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 0.75rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    border: 1px solid #c3e6cb;
}

.submit-button {
    width: 100%;
    margin-top: 2rem;
    padding: 0.75rem;
    background-color: #ddd;
    color: #222;
    border: none;
    font-size: 1rem;
    font-weight: 400;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.submit-button:hover:not(:disabled) {
    background-color: #bbb;
}

.submit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.form-footer {
    text-align: center;
    margin-top: 1rem;
    font-size: 0.95rem;
}

.form-footer a {
    color: #888;
    text-decoration: underline;
    transition: color 0.3s ease;
}

.form-footer a:hover {
    color: #666;
}

@media screen and (max-width: 480px) {
    .login-page {
        background-color: #ffffff;
        /* スクロール可能にするため height を min-height に変更 */
        height: auto;
        min-height: 100vh;
        /* コンテンツがはみ出した時にスクロールできるように */
        overflow-y: auto;
        /* コンテンツを上寄せに */
        align-items: flex-start;
        /* 上部に少し余白を追加 */
        padding-top: 20px;
        box-sizing: border-box;
    }

    .form-container {
        box-shadow: none;
        border-radius: 0;
        margin: 10px;
        max-width: 100%;
        padding: 1rem;
        /* 下部に余白を追加してスクロール余地を確保 */
        margin-bottom: 30px;
    }
}


</style>