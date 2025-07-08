<template>
  <div class="comments-page">
    <h1>Comments</h1>

    <!-- シンプルな検索フォーム（最小限） -->
    <div class="simple-search">
      <input 
        v-model="searchFilters.keyword" 
        type="text" 
        placeholder="コメント・ユーザー名・レシピ名で検索"
        @input="debouncedSearch"
        class="search-input"
      >
      <button @click="clearSearch" class="clear-btn" v-if="searchFilters.keyword">クリア</button>
    </div>

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
        <tr v-if="loading">
          <td colspan="6" style="text-align: center; color: #666;">読み込み中...</td>
        </tr>
        <tr v-else-if="comments.length === 0">
          <td colspan="6" style="text-align: center; color: #666;">コメントが見つかりません</td>
        </tr>
        <tr v-else v-for="comment in comments" :key="comment.id">
          <td data-label="ID">{{ comment.id }}</td>
          <td data-label="ユーザー名">{{ comment.user.name }}</td>
          <td data-label="レシピ">{{ comment.recipe.title }}</td>
          <td data-label="コメント">{{ comment.content }}</td>
          <td data-label="投稿日">{{ formatDate(comment.created_at) }}</td>
          <td data-label="操作">
            <button @click="deleteComment(comment.id)">削除</button>
          </td>
        </tr>
      </tbody>
    </table>
    <!-- ページネーション -->
    <div class="pagination" v-if="totalPages > 1">
      <button 
        @click="goToPage(currentPage - 1)"
        :disabled="currentPage <= 1"
        class="pagination-btn"
      >
        ＜
      </button>

      <span 
        v-for="page in totalPages" 
        :key="page"
        :class="{ active: page === currentPage }"
        @click="goToPage(page)"
        class="pagination-number"
      >
        {{ page }}
      </span>

      <button 
        @click="goToPage(currentPage + 1)"
        :disabled="currentPage >= totalPages"
        class="pagination-btn"
      >
        ＞
      </button>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})

import { ref, computed, onMounted } from 'vue'
import { useAuth } from '~/composables/useAuth'
import { useRoute, useRouter } from '#app'

// データ定義
const comments = ref([])
const loading = ref(false)

// ページネーション関連
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = 10 // 1ページあたり10件

// 検索フィルター（シンプル版）
const searchFilters = ref({
  keyword: ''
})

// ルーター
const route = useRoute()
const router = useRouter()

// 認証
const { getIdToken } = useAuth()

// 全コメントデータ（モック - 22件）
const allCommentsData = [
  { id: 1, user: { name: 'テストユーザーA' }, recipe: { title: 'チキンカレー' }, content: 'めっちゃ美味しかったです！', created_at: '2025-01-10' },
  { id: 2, user: { name: 'ユーザーB' }, recipe: { title: 'パスタボロネーゼ' }, content: '今度作ってみます〜', created_at: '2025-01-11' },
  { id: 3, user: { name: 'CookingLover2024' }, recipe: { title: 'ハンバーグ' }, content: 'プロ級の仕上がりになりました！', created_at: '2025-01-12' },
  { id: 4, user: { name: 'グルメ太郎' }, recipe: { title: '親子丼' }, content: '簡単で美味しい！', created_at: '2025-01-13' },
  { id: 5, user: { name: 'ママの料理' }, recipe: { title: 'グラタン' }, content: '子供たちがおかわりしてくれました♪', created_at: '2025-01-14' },
  { id: 6, user: { name: 'VeryLongUserName' }, recipe: { title: '麻婆豆腐' }, content: '材料も揃えやすくて助かります！', created_at: '2025-01-15' },
  { id: 7, user: { name: 'レシピマスター' }, recipe: { title: 'オムライス' }, content: '包むコツを教えてください', created_at: '2025-01-16' },
  { id: 8, user: { name: '料理初心者' }, recipe: { title: 'チャーハン' }, content: '初めて作りました！', created_at: '2025-01-17' },
  { id: 9, user: { name: 'ベジタリアン' }, recipe: { title: '野菜炒め' }, content: 'ヘルシーで最高です', created_at: '2025-01-18' },
  { id: 10, user: { name: 'スパイス好き' }, recipe: { title: 'カレー' }, content: 'スパイスの配合が絶妙', created_at: '2025-01-19' },
  { id: 11, user: { name: '主婦A' }, recipe: { title: '肉じゃが' }, content: '家族に大好評でした', created_at: '2025-01-20' },
  { id: 12, user: { name: '大学生' }, recipe: { title: 'ラーメン' }, content: '一人暮らしに最適！', created_at: '2025-01-21' },
  { id: 13, user: { name: 'シェフ志望' }, recipe: { title: 'パスタ' }, content: 'プロの技術が学べました', created_at: '2025-01-22' },
  { id: 14, user: { name: '健康志向' }, recipe: { title: 'サラダ' }, content: 'ダイエットにも良さそう', created_at: '2025-01-23' },
  { id: 15, user: { name: 'お菓子作り' }, recipe: { title: 'ケーキ' }, content: 'デコレーションも可愛い', created_at: '2025-01-24' },
  { id: 16, user: { name: 'パン好き' }, recipe: { title: 'フランスパン' }, content: '外はカリッと中はもちもち', created_at: '2025-01-25' },
  { id: 17, user: { name: '魚料理愛好家' }, recipe: { title: '煮魚' }, content: '臭みが全くない！', created_at: '2025-01-26' },
  { id: 18, user: { name: '時短料理' }, recipe: { title: '丼もの' }, content: '忙しい日に助かります', created_at: '2025-01-27' },
  { id: 19, user: { name: '子育てママ' }, recipe: { title: '幼児食' }, content: '子供が喜んで食べました', created_at: '2025-01-28' },
  { id: 20, user: { name: '節約生活' }, recipe: { title: 'もやし炒め' }, content: '安くて美味しい！', created_at: '2025-01-29' },
  { id: 21, user: { name: '料理研究家' }, recipe: { title: '創作料理' }, content: 'アイデアが素晴らしい', created_at: '2025-01-30' },
  { id: 22, user: { name: 'グルメ評論家' }, recipe: { title: '高級料理' }, content: 'レストラン級の味', created_at: '2025-01-31' }
]

// 初期データ読み込み
onMounted(async () => {
  // URLクエリからページ番号を取得
  currentPage.value = parseInt(route.query.page) || 1
  searchFilters.value.keyword = route.query.keyword || ''
  
  await loadComments()
})

// コメント一覧取得
const loadComments = async () => {
  loading.value = true
  try {
    // TODO: API実装時に有効化
    /*
    const token = await getIdToken()
    const config = useRuntimeConfig()
    
    const response = await $fetch('/admin/comments', {
      baseURL: config.public.apiBaseUrl,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      query: {
        page: currentPage.value,
        per_page: perPage,
        keyword: searchFilters.value.keyword
      }
    })
    
    comments.value = response.data
    totalPages.value = response.last_page
    currentPage.value = response.current_page
    */
    
    // モックデータでページネーション機能をシミュレート
    let filteredData = allCommentsData
    
    // 検索フィルタリング
    if (searchFilters.value.keyword) {
      const keyword = searchFilters.value.keyword.toLowerCase()
      filteredData = allCommentsData.filter(comment => 
        comment.user.name.toLowerCase().includes(keyword) ||
        comment.recipe.title.toLowerCase().includes(keyword) ||
        comment.content.toLowerCase().includes(keyword)
      )
    }
    
    // ページネーション計算
    totalPages.value = Math.ceil(filteredData.length / perPage)
    
    // 現在ページのデータを取得
    const start = (currentPage.value - 1) * perPage
    const end = start + perPage
    comments.value = filteredData.slice(start, end)
    
    console.log(`コメント一覧を読み込みました: ${comments.value.length}件`)
    
  } catch (error) {
    console.error('コメント取得エラー:', error)
  } finally {
    loading.value = false
  }
}

// 検索実行
const searchComments = async () => {
  currentPage.value = 1 // 検索時は1ページ目に戻る
  updateUrl()
  await loadComments()
}

// 検索クリア
const clearSearch = () => {
  searchFilters.value.keyword = ''
  currentPage.value = 1
  updateUrl()
  loadComments()
}

// ページ遷移
const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  updateUrl()
  loadComments()
}

// URL更新
const updateUrl = () => {
  const query = {}
  if (searchFilters.value.keyword) query.keyword = searchFilters.value.keyword
  if (currentPage.value > 1) query.page = currentPage.value
  router.push({ path: '/admin/comments', query })
}

// デバウンス検索（入力中の連続検索を防ぐ）
let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (searchFilters.value.keyword.length > 0) {
      searchComments()
    } else {
      clearSearch()
    }
  }, 500)
}

// 日付フォーマット
const formatDate = (datetime) => {
  const date = new Date(datetime)
  return date.toISOString().split('T')[0]
}

// コメント削除
const deleteComment = async (id) => {
  if (!confirm('本当に削除しますか？')) return

  try {
    // TODO: API実装時に有効化
    /*
    const token = await getIdToken()
    const config = useRuntimeConfig()
    
    await $fetch(`/admin/comments/${id}`, {
      baseURL: config.public.apiBaseUrl,
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })
    */

    // 削除後にリスト更新
    const index = allCommentsData.findIndex(comment => comment.id === id)
    if (index !== -1) {
      allCommentsData.splice(index, 1)
    }
    
    // 現在ページが空になった場合は前ページに移動
    await loadComments()
    if (comments.value.length === 0 && currentPage.value > 1) {
      currentPage.value = currentPage.value - 1
      updateUrl()
      await loadComments()
    }
    
    console.log(`コメント${id}を削除しました`)
    
  } catch (error) {
    console.error('削除エラー:', error)
    alert('削除に失敗しました')
  }
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

/* シンプルな検索フォーム */
.simple-search {
    width: 90%;
    margin: 15px auto;
    display: flex;
    align-items: center;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #aaa;
    border-radius: 4px;
    font-size: 14px;
}

.clear-btn {
    background-color: #6c757d;
    color: white;
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.clear-btn:hover {
    background-color: #545b62;
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
    border-bottom: 1px solid #bbb;
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

/* ページネーション */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin: 30px 0;
}

.pagination-btn {
    background-color: #f5f5f5;
    color: #333;
    border: 1px solid #ccc;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.pagination-btn:hover:not(:disabled) {
    background-color: #e9e9e9;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-number {
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    color: #333;
}

.pagination-number:hover {
    background-color: #f0f0f0;
}

.pagination-number.active {
    background-color: #ff6b6b;
    color: white;
}



@media screen and (max-width: 768px) {
    .simple-search {
        flex-direction: column;
        gap: 8px;
    }
    
    .search-input {
        width: 100%;
    }

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

    .pagination {
        flex-wrap: wrap;
        gap: 5px;
    }

    .pagination-btn,
    .pagination-number {
        padding: 6px 10px;
        font-size: 12px;
    }
}
</style>