<template>
  <div>
    <Head>
      <Title>一般ユーザー</Title>
      <Meta charset="UTF-8" />
      <Meta name="viewport" content="width=device-width, initial-scale=1.0" />
    </Head>

    <header>
      <div class="container">
        <div class="logo">
          <img src="/images/rabbit-shape.svg" alt="Logo" class="logo-image" />
          <span class="logo-text">Vanilla's Kitchen</span>
        </div>
        <nav>
          <ul>
            <li><NuxtLink to="/user">Recipes</NuxtLink></li>
            <li v-if="isAuthenticated"><NuxtLink to="/user/favorite">Favorite</NuxtLink></li>
            <li v-if="isAuthenticated"><NuxtLink to="/user/profile">Profile</NuxtLink></li>
            <li v-if="isAuthenticated"><a href="#" @click.prevent="logout">Logout</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <main>
      <NuxtPage />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const isAuthenticated = ref(false) // 初期値をfalseに

// 認証状態の確認
const checkAuthStatus = async () => {
  try {
    // 実際のAPIコール例（Cookie/JWTベースの認証の場合）
    const user = await $fetch('/api/auth/me')
    isAuthenticated.value = !!user
  } catch (error) {
    // 認証エラーの場合は未ログイン扱い
    isAuthenticated.value = false
  }
}

// ログアウト処理
const logout = async () => {
  try {
    await $fetch('/api/logout', { method: 'POST' })
    isAuthenticated.value = false
    await navigateTo('/login')
  } catch (error) {
    console.error('Logout failed:', error)
  }
}

// 初期化時に認証状態をチェック（クライアントサイドのみ）
onMounted(() => {
  // 一時的にログイン状態にする場合
  isAuthenticated.value = true
  
  // 実際のAPIを使う場合は以下をコメントアウト解除
  // checkAuthStatus()
})

// ルート変更時にも認証状態をチェック（必要に応じて）
watch(() => useRoute().path, () => {
  if (process.client) {
    checkAuthStatus()
  }
})
</script>

<style>
@import "@/assets/css/common.css";
</style>