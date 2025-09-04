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
            <li v-if="isLoggedIn">
              <NuxtLink to="/user/favorite" :class="{ active: $route.path === '/user/favorite' }">
                Favorite
              </NuxtLink>
            </li>
            <li v-if="isLoggedIn">
              <NuxtLink to="/user/profile" :class="{ active: $route.path === '/user/profile' }">
                Profile
              </NuxtLink>
            </li>
            <li v-if="isLoggedIn">
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
const { logout, isLoggedIn } = useAuth()

const favoriteStore = useState('favorites', () => new Set())

const handleLogout = async () => {
  try {
    await logout()
    favoriteStore.value.clear()
  } catch (error) {
    console.error('❌ Layout: ログアウト失敗:', error)
  }
}
</script>

<style>
@import "@/assets/css/common.css";
</style>