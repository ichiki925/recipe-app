<template>
    <div class="register-page">
        <div class="form-container">
            <form class="form" @submit.prevent="handleSubmit">
            <div class="logo">
                    <img src="/images/rabbit-shape.svg" alt="Rabbit Logo" class="logo-image">
                </div>
                <h1 class="title">Sign up</h1>
                <div class="form-group">
                    <label class="form-label">ユーザーネーム</label>
                    <input
                        type="text"
                        class="form-input"
                        v-model="form.name"
                        :class="{ 'error-input': errors.name }"
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
                    >
                    <div v-if="errors.password_confirmation" class="error">{{ errors.password_confirmation }}</div>
                </div>

                <button type="submit" class="submit-btn" :disabled="loading">
                {{ loading ? '登録中...' : '登録' }}
                </button>
            </form>

            <NuxtLink to="/login" class="login-link">ログインはこちら</NuxtLink>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    title: 'サインアップ',
    layout: false  // ← ここを必ず false に！
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
        // APIエンドポイントに送信
        const { data } = await $fetch('/api/register', {
        method: 'POST',
        body: form
        })

        // 成功時の処理（例：ダッシュボードへリダイレクト）
        await navigateTo('/dashboard')

    } catch (error) {
        // バリデーションエラーの処理
        if (error.data && error.data.errors) {
        errors.value = error.data.errors
        } else {
        // その他のエラー
        console.error('Registration error:', error)
        }
    } finally {
        loading.value = false
    }
}

</script>

<style scoped>
.register-page {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    
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
    padding: 2rem; /* 復活 */
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