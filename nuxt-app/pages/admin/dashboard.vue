<template>
  <div class="dashboard-container">
    <h1>管理者ダッシュボード</h1>

    <div class="dashboard-stats">
      <div class="dashboard-card">
        <span>全レシピ数</span>
        <strong>{{ totalRecipes }} 件</strong>
      </div>
      <div class="dashboard-card">
        <span>最近更新されたレシピ</span>
        <strong>{{ recentUpdatedRecipes }} 件</strong>
      </div>
      <div class="dashboard-card">
        <span>ユーザー登録数</span>
        <strong>{{ totalUsers }} 件</strong>
      </div>
    </div>

    <div class="admin-menu">
      <NuxtLink to="/admin/recipes" class="admin-button">📋 レシピ一覧</NuxtLink>
      <NuxtLink to="/admin/recipes/create" class="admin-button">➕ レシピ新規作成</NuxtLink>
      <NuxtLink to="/admin/recipes/edit" class="admin-button">✏️ レシピ編集</NuxtLink>
      <NuxtLink to="/admin/comments" class="admin-button">💬 コメント管理</NuxtLink>
    </div>

    <div class="recent-deleted">
      <h2>🗑 最近削除されたレシピ</h2>
      <ul class="deleted-list">
        <li v-for="recipe in deletedRecipes" :key="recipe.id">
          {{ recipe.title }}
          <NuxtLink :to="`/admin/recipes/${recipe.id}/edit`">編集</NuxtLink>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})

// 認証とデータ取得
const { getIdToken, waitForAuth } = useAuth()
const config = useRuntimeConfig()

// リアクティブデータ
const dashboardData = ref({
  stats: {},
  deleted_recipes: [],
  recent_activities: [],
  popular_recipes: []
})

const isLoading = ref(true)

// API呼び出し
onMounted(async () => {
  const currentUser = await waitForAuth()
  if (!currentUser) {
    await navigateTo('/auth/login')
    return
  }

  try {
    const token = await getIdToken()
    const response = await $fetch('/admin/dashboard', {
      baseURL: config.public.apiBaseUrl,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })
    
    dashboardData.value = response.data
  } catch (error) {
    console.error('ダッシュボードデータ取得エラー:', error)
  } finally {
    isLoading.value = false
  }
})

// computed プロパティ
const totalRecipes = computed(() => dashboardData.value.stats.total_recipes || 0)
const recentUpdatedRecipes = computed(() => dashboardData.value.stats.recent_updated_recipes || 0)
const totalUsers = computed(() => dashboardData.value.stats.total_users || 0)
const deletedRecipes = computed(() => dashboardData.value.deleted_recipes || [])
</script>

<style scoped>
@import '@/assets/css/common.css';
/* 全体のレイアウト */
.dashboard-container {
    padding: 30px;
    font-family: 'Arial', sans-serif;
    color: #333;
}

/* タイトル */
.dashboard-container h1 {
    font-size: 28px;
    font-family: serif;
    font-weight: lighter;
    margin-bottom: 20px;
    border-left: 6px solid #888;
    padding-left: 10px;
}

/* 情報カード */
.dashboard-stats {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
}

.dashboard-card {
    background-color: #fff;
    border: 1px solid #ddd;
    border-left: 6px solid #999;
    padding: 15px 20px;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    flex: 1;
}

.dashboard-card span {
    font-size: 14px;
    color: #777;
}

.dashboard-card strong {
    font-size: 22px;
    display: block;
    margin-top: 5px;
}

.admin-menu {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.admin-button {
    background-color: #eee;
    border: 1px solid #ccc;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-family: cursive;
    color: #333;
    transition: background 0.2s ease;
}

.admin-button:hover {
    background-color: #ddd;
}

/* 最近削除の見出し */
.recent-deleted h2 {
    font-family: serif;
    font-weight: lighter;
    font-size: 20px;
    margin-top: 40px;
    margin-bottom: 10px;
}

/* 削除リスト */
.deleted-list {
    list-style: none;
    padding: 0;
}

.deleted-list li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
}

@media screen and (max-width: 768px) {
    .dashboard-stats {
        flex-direction: column;
        gap: 15px;
    }

    .admin-menu {
        flex-direction: column;
        gap: 10px;
    }

    .dashboard-card {
        width: 100%;
    }

    .admin-button {
        width: 100%;
        text-align: center;
    }

    .dashboard-container {
        padding: 15px;
    }
}
</style>
