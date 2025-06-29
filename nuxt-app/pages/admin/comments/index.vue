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

body {
    background-color: #fff;
}

h1 {
    font-family: cursive;
    text-align: center;
    margin-top: 30px;
}

table {
    width: 90%;
    margin: 20px auto;
    border-collapse: collapse;
    background-color: #fff;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    border-radius: 8px;
    overflow: hidden;
}

thead {
    background-color: #f0f0f0;
}

thead th {
    padding: 12px;
    text-align: left;
    font-size: 14px;
    color: #555;
    border-bottom: 1px solid #ccc;
}

tbody td {
    padding: 12px;
    font-size: 14px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}

tbody tr:hover {
    background-color: #f9f9f9;
}

button {
    background-color: #ff6b6b;
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

button:hover {
    background-color: #e63946;
}

@media screen and (max-width: 768px) {

    table,
    thead,
    tbody,
    th,
    td,
    tr {
        display: block;
    }

    thead {
        display: none;
        /* ヘッダーは非表示に */
    }

    tbody tr {
        background-color: #fff;
        margin: 10px;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
    }

    tbody td {
        padding: 8px 0;
        font-size: 14px;
        border-bottom: none;
    }

    tbody td::before {
        content: attr(data-label);
        font-weight: bold;
        display: inline-block;
        width: 80px;
    }

    button {
        font-size: 13px;
        padding: 4px 8px;
    }


}
</style>
