<!-- pages/admin/register.vue -->
<template>
    <div class="register-page">
        <div class="form-container">
            <form class="form" @submit.prevent="handleRegister">
            <div class="logo">
                <img src="/images/rabbit-shape.svg" alt="Rabbit Logo" class="logo-image">
            </div>
            <h1 class="title">Admin Sign up</h1>

            <!-- エラーメッセージ -->
            <div v-if="error" class="error-message">
                {{ error }}
            </div>

            <!-- 成功メッセージ -->
            <div v-if="success" class="success-message">
                アカウントが作成されました。メール認証を確認してください。
            </div>

            <!-- 管理者認証コード -->
            <div class="form-group">
                <label class="form-label">管理者認証コード</label>
                <input
                type="password"
                class="form-input"
                v-model="form.adminCode"
                :class="{ 'error-input': errors.adminCode }"
                placeholder="管理者コードを入力"
                required
                >
                <div v-if="errors.adminCode" class="error">{{ errors.adminCode }}</div>
            </div>

            <!-- 管理者名 -->
            <div class="form-group">
                <label class="form-label">管理者名</label>
                <input
                type="text"
                class="form-input"
                v-model="form.name"
                :class="{ 'error-input': errors.name }"
                placeholder="管理者名を入力"
                required
                >
                <div v-if="errors.name" class="error">{{ errors.name }}</div>
            </div>

            <!-- メールアドレス -->
            <div class="form-group">
                <label class="form-label">メールアドレス</label>
                <input
                type="email"
                class="form-input"
                v-model="form.email"
                :class="{ 'error-input': errors.email }"
                placeholder="admin@example.com"
                required
                >
                <div v-if="errors.email" class="error">{{ errors.email }}</div>
            </div>

            <!-- パスワード -->
            <div class="form-group">
                <label class="form-label">パスワード</label>
                <input
                type="password"
                class="form-input"
                v-model="form.password"
                :class="{ 'error-input': errors.password }"
                placeholder="6文字以上のパスワード"
                minlength="6"
                required
                >
                <div v-if="errors.password" class="error">{{ errors.password }}</div>
            </div>

            <!-- パスワード確認 -->
            <div class="form-group">
                <label class="form-label">パスワード確認</label>
                <input
                type="password"
                class="form-input"
                v-model="form.passwordConfirm"
                :class="{ 'error-input': errors.passwordConfirm }"
                placeholder="パスワードを再入力"
                required
                >
                <div v-if="errors.passwordConfirm" class="error">{{ errors.passwordConfirm }}</div>
            </div>

            <!-- 注意事項 -->
            <div class="warning-box">
                <div class="warning-title">重要な注意事項</div>
                <ul class="warning-list">
                <li>管理者アカウントは重要な権限を持ちます</li>
                <li>登録後、必ずメール認証を完了してください</li>
                <li>パスワードは安全な場所に保管してください</li>
                </ul>
            </div>

            <button type="submit" class="submit-btn" :disabled="loading">
                {{ loading ? '登録中...' : '管理者アカウントを作成' }}
            </button>
            </form>

            <NuxtLink to="/auth/login" class="login-link">ログイン画面に戻る</NuxtLink>
        </div>
    </div>
</template>

<script setup>
    // 管理者専用ページなので、メタ情報を設定
    definePageMeta({
        title: '管理者登録',
        layout: false // レイアウトを使用しない
    })

    // リアクティブデータ
    const form = reactive({
        adminCode: '',
        name: '',
        email: '',
        password: '',
        passwordConfirm: ''
    })

    const loading = ref(false)
    const error = ref('')
    const success = ref(false)
    const errors = ref({})

    // 認証用のcomposable（後で作成）
    // const { registerAdmin } = useAuth()

    // 登録処理
    const handleRegister = async () => {
        try {
        loading.value = true
        error.value = ''
        success.value = false
        errors.value = {}

        // バリデーション
        if (!validateForm()) {
            return
        }

        // TODO: Firebase Authentication + Laravel API
        // await registerAdmin(form)

        // 仮の処理（実際の実装時に置き換え）
        console.log('管理者登録:', form)

        // 成功時の処理
        success.value = true

        // フォームリセット
        Object.keys(form).forEach(key => {
            form[key] = ''
        })

        } catch (err) {
        error.value = err.message || '登録に失敗しました'
        } finally {
        loading.value = false
        }
    }

    // バリデーション
    const validateForm = () => {
        const newErrors = {}

        // 管理者コードチェック
        if (!form.adminCode) {
        newErrors.adminCode = '管理者認証コードが必要です'
        }

        // 名前チェック
        if (!form.name) {
        newErrors.name = '管理者名が必要です'
        }

        // メールチェック
        if (!form.email) {
        newErrors.email = 'メールアドレスが必要です'
        }

        // パスワードチェック
        if (!form.password) {
        newErrors.password = 'パスワードが必要です'
        } else if (form.password.length < 6) {
        newErrors.password = 'パスワードは6文字以上で入力してください'
        }

        // パスワード確認チェック
        if (form.password !== form.passwordConfirm) {
        newErrors.passwordConfirm = 'パスワードが一致しません'
        }

        errors.value = newErrors

        if (Object.keys(newErrors).length > 0) {
        error.value = '入力内容を確認してください'
        return false
        }

        return true
    }
</script>

<style scoped>
    .register-page {
        min-height: 100vh;
        width: 100%;
        overflow-y: auto;

        background-color: #f2f2f2;
        font-family: 'Noto Sans JP', sans-serif;
        color: #555;
        font-weight: 300;

        display: flex;
        align-items: center;
        justify-content: center;

        margin: 0;
        padding: 2rem 0;
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
        color: #555;
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

    .error-message {
        background-color: #fdeaea;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .success-message {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .warning-box {
        background-color: #f7cec8;
        border: 1px solid #fbb199;
        border-radius: 4px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        text-align: left;
    }

    .warning-title {
        font-weight: 500;
        color: #856404;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .warning-list {
        color: #856404;
        font-size: 0.85rem;
        margin: 0;
        padding-left: 1.2rem;
    }

    .warning-list li {
        margin-bottom: 0.3rem;
    }

    .submit-btn {
        width: 100%;
        margin-top: 2rem;
        padding: 0.8rem;
        background-color: #dcdcdc;
        color: #555;
        border: none;
        font-size: 1rem;
        font-weight: 300;
        cursor: pointer;
        border-radius: 4px;
        transition: background-color 0.3s ease;
    }

    .submit-btn:hover {
        background-color: #cfcfcf;
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
        color: #555;
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
        }

        .form-container {
        box-shadow: none;
        border-radius: 0;
        margin: 30px;
        max-width: 100%;
        padding: 1rem;
        }
    }
</style>