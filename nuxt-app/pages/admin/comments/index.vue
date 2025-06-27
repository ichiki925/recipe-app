<template>
  <div class="comments-page">
    <h1>Comments</h1>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>ユーザー名</th>
          <th>レシピ</th>
          <th>コメント</th>
          <th>投稿日</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <!-- <tr>
          <td data-label="ID">1</td>
          <td data-label="ユーザー名">テストユーザー</td>
          <td data-label="レシピ">オムライス</td>
          <td data-label="コメント">簡単で美味しい！</td>
          <td data-label="投稿日">2025-06-27</td>
          <td data-label="操作">
            <button>削除</button>
          </td>
        </tr> -->
        <tr v-for="comment in comments" :key="comment.id">
          <td>{{ comment.id }}</td>
          <td>{{ comment.user.name }}</td>
          <td>{{ comment.recipe.title }}</td>
          <td>{{ comment.body }}</td>
          <td>{{ formatDate(comment.created_at) }}</td>
          <td>
            <form @submit.prevent="deleteComment(comment.id)">
              <button type="submit">削除</button>
            </form>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})
import { ref, onMounted } from 'vue'
import { useFetch } from '#app'

const comments = ref([])

onMounted(async () => {
  const { data } = await useFetch('/api/admin/comments')
  comments.value = data.value
})

const formatDate = (datetime) => {
  const date = new Date(datetime)
  return date.toISOString().split('T')[0]
}

const deleteComment = async (id) => {
  if (!confirm('本当に削除しますか？')) return

  await fetch(`/api/admin/comments/${id}`, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
    },
  })

  // 削除後にリスト更新
  comments.value = comments.value.filter(comment => comment.id !== id)
}
</script>

<style scoped>
@import '~/assets/css/admin/comments/index.css';
</style>
