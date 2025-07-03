<template>
    <div class="register-page">
        <div class="form-container">
            <form class="form" @submit.prevent="handleSubmit">
                <div class="logo">
                    <img src="/images/rabbit-shape.svg" alt="Rabbit Logo" class="logo-image">
                </div>
                <h1 class="title">Sign up</h1>
                
                <!-- 全般エラーメッセージ -->
                <div v-if="errors.general" class="error general-error">{{ errors.general }}</div>
                
                <div class="form-group">
                    <label class="form-label">ユーザーネーム</label>
                    <input
                        type="text"
                        class="form-input"
                        v-model="form.name"
                        :class="{ 'error-input': errors.name }"
                        required
                    >
                    <div v-if="errors.name" class="error">{{ errors.name }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">メールアドレス</label>
                    <input
                        type="email"
                        class="form-input"
                        v-model="form.email"
                        :class="{ 'error-input': errors.email }"
                        required
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
                        minlength="6"
                    >
                    <div v-if="errors.password" class="error">{{ errors.password }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">パスワード確認</label>
                    <input
                        type="password"
                        class="form-input"
                        v-model="form.password_confirmation"
                        :class="{ 'error-input': errors.password_confirmation }"
                        required
                    >
                    <div v-if="errors.password_confirmation" class="error">{{ errors.password_confirmation }}</div>
                </div>

                <button type="submit" class="submit-btn" :disabled="loading">
                    {{ loading ? '登録中...' : '登録' }}
                </button>
            </form>

            <NuxtLink to="/auth/login" class="login-link">ログインはこちら</NuxtLink>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    title: 'サインアップ',
    layout: false
})

// リアクティブなフォームデータ
const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
})

// エラー状態
const errors = ref({})
const loading = ref(false)

// フォーム送信処理
const handleSubmit = async () => {
    loading.value = true
    errors.value = {}

    try {
        // バリデーション
        if (!validateForm()) {
            return
        }

        // useAuth の register 関数を使用
        const { register } = useAuth()
        
        console.log('🚀 登録処理開始:', form.email)
        
        await register(form.email, form.password, form.name)
        
        // 成功時の処理
        console.log('✅ 登録成功！ログイン画面に遷移します')
        
        // ログイン画面にリダイレクト
        await navigateTo('/auth/login?registered=true')

    } catch (error) {
        console.error('❌ 登録エラー:', error)
        
        // Firebase のエラーメッセージを日本語化
        let errorMessage = 'エラーが発生しました'
        
        if (error.code) {
            switch (error.code) {
                case 'auth/email-already-in-use':
                    errorMessage = 'このメールアドレスは既に使用されています'
                    break
                case 'auth/invalid-email':
                    errorMessage = '無効なメールアドレスです'
                    break
                case 'auth/weak-password':
                    errorMessage = 'パスワードは6文字以上で入力してください'
                    break
                case 'auth/operation-not-allowed':
                    errorMessage = 'メール/パスワード認証が無効になっています'
                    break
                default:
                    errorMessage = error.message || 'エラーが発生しました'
            }
        }
        
        errors.value.general = errorMessage
    } finally {
        loading.value = false
    }
}

// バリデーション関数
const validateForm = () => {
    let isValid = true
    
    // ユーザーネームチェック
    if (!form.name.trim()) {
        errors.value.name = 'ユーザーネームを入力してください'
        isValid = false
    }
    
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
    } else if (form.password.length < 6) {
        errors.value.password = 'パスワードは6文字以上で入力してください'
        isValid = false
    }
    
    // パスワード確認チェック
    if (!form.password_confirmation) {
        errors.value.password_confirmation = 'パスワード確認を入力してください'
        isValid = false
    } else if (form.password !== form.password_confirmation) {
        errors.value.password_confirmation = 'パスワードが一致しません'
        isValid = false
    }
    
    return isValid
}
</script>

<style scoped>
.register-page {
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

    overflow-y: auto;
}

.form-container {
    max-width: 400px;
    width: 90%;
    padding: 2rem;
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.form {
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

.title {
    text-align: center;
    font-size: 1.5rem;
    font-family: cursive;
    margin-bottom: 2rem;
    font-weight: 300;
    color: #222;
}

.form-group {
    margin-bottom: 1.5rem;
    text-align: left;
}

.form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.9rem;
    font-weight: 300;
    color: #333;
}

.form-input {
    width: 100%;
    padding: 0.6rem 0.8rem;
    border: none;
    border-bottom: 1px solid #ccc;
    background-color: #fff;
    font-size: 1rem;
    font-weight: 400;
    outline: none;
    transition: border-bottom-color 0.3s ease;
    box-sizing: border-box;
}

.form-input:focus {
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

.general-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    text-align: center;
}

.submit-btn {
    width: 100%;
    margin-top: 2rem;
    padding: 0.8rem;
    background-color: #ddd;
    color: #222;
    border: none;
    font-size: 1rem;
    font-weight: 300;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.submit-btn:hover {
    background-color: #bbb;
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.login-link {
    display: block;
    text-align: center;
    margin-top: 1.2rem;
    font-size: 0.85rem;
    color: #333;
    text-decoration: underline;
    font-weight: 300;
    transition: color 0.3s ease;
}

.login-link:hover {
    color: #9f9b9b;
}

@media screen and (max-width: 480px) {
    .register-page {
        background-color: #ffffff;
        /* スクロール可能にするため height を min-height に変更 */
        height: auto;
        min-height: 120vh; /* 画面より高くしてスクロール余地を確保 */
        /* コンテンツがはみ出した時にスクロールできるように */
        overflow-y: auto;
        /* コンテンツを上寄せに */
        align-items: flex-start;
        /* 上部の余白をほぼなくす */
        padding-top: 5px;
        padding-bottom: 50px; /* 下部に余白を追加 */
        box-sizing: border-box;
    }

    .form-container {
        box-shadow: none;
        border-radius: 0;
        margin: 3px;
        max-width: 100%;
        padding: 0.8rem;
        /* 下部に余白を追加してスクロール余地を確保 */
        margin-bottom: 60px;
    }
    
    .title {
        font-size: 1.2rem;
        margin-bottom: 1rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .submit-btn {
        margin-top: 2rem;
        padding: 0.7rem;
    }
    
    .login-link {
        margin-top: 1rem;
        margin-bottom: 2rem; /* リンクの下にも余白を追加 */
    }
}
</style>