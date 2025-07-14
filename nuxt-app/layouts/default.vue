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
            <li>
              <NuxtLink to="/user" :class="{ active: $route.path === '/user' }">
                Recipes
              </NuxtLink>
            </li>
            <li v-if="isAuthenticated">
              <NuxtLink to="/user/favorite" :class="{ active: $route.path === '/user/favorite' }">
                Favorite
              </NuxtLink>
            </li>
            <li v-if="isAuthenticated">
              <NuxtLink to="/user/profile" :class="{ active: $route.path === '/user/profile' }">
                Profile
              </NuxtLink>
            </li>
            <li v-if="isAuthenticated">
              <a href="#" @click.prevent="handleLogout" class="logout-link">
                Logout
              </a>
            </li>
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
import { ref, onMounted, computed } from 'vue'

const isAuthenticated = ref(false)

const { logout, user, isLoggedIn } = useAuth()

// お気に入り状態管理用のグローバルストア
const favoriteStore = useState('favorites', () => new Set())

// お気に入り件数の計算
const favoriteCount = computed(() => {
  return favoriteStore.value.size
})

// 認証状態の確認
const checkAuthStatus = () => {
  try {
    if (isLoggedIn.value && user.value) {
      isAuthenticated.value = true
      console.log('🔐 Layout: ユーザー認証済み', user.value.email)
    } else {
      isAuthenticated.value = false
      console.log('⚠️ Layout: 未認証ユーザー')
    }
  } catch (error) {
    console.error('❌ Layout: 認証確認エラー:', error)
    isAuthenticated.value = false
  }
}

// Firebase認証対応ログアウト処理
const handleLogout = async () => {
  try {
    console.log('🚪 Layout: ログアウト開始')
    await logout()
    isAuthenticated.value = false
    
    // お気に入り情報もクリア
    favoriteStore.value.clear()
    
    console.log('✅ Layout: ログアウト成功')

  } catch (error) {
    console.error('❌ Layout: ログアウト失敗:', error)
  }
}

// 初期化時に認証状態をチェック
onMounted(() => {
  checkAuthStatus()
})

// ユーザー状態の変化を監視
watch([user, isLoggedIn], () => {
  checkAuthStatus()
})

// ルート変更時にも認証状態をチェック
watch(() => useRoute().path, () => {
    checkAuthStatus()
})
</script>

<style>
@import "@/assets/css/common.css";
</style>