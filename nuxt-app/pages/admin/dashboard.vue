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
      <NuxtLink to="/admin/recipes/create" class="admin-button">➕ 新規作成</NuxtLink>
      <NuxtLink to="/admin/recipes/edit" class="admin-button">✏️ 編集</NuxtLink>
      <NuxtLink to="/admin/comments" class="admin-button">💬 コメント管理</NuxtLink>
      <NuxtLink to="/admin/profile" class="admin-button">👤 プロフィール</NuxtLink>
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
const props = defineProps({
  totalRecipes: Number,
  recentUpdatedRecipes: Number,
  totalUsers: Number,
  deletedRecipes: Array
})
</script>

<style scoped>
@import '@/assets/css/common.css';
@import '@/assets/css/admin/dashboard.css';
</style>
