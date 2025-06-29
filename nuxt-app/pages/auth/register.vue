<template>
    <div register-page>
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

<style>
@import "@/assets/css/auth/register.css";
</style>


