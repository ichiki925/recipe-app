<template>
  <div class="recipe-create-container">
    <!-- プレビューエリア -->
    <div class="image-preview" @click="selectImage">
      <span v-if="!previewUrl" id="preview-text">No Image</span>
      <img
        id="preview-image"
        v-if="previewUrl"
        :src="previewUrl"
        alt="プレビュー"
      />
    </div>

    <!-- 非表示のファイル入力 -->
    <input
      type="file"
      id="imageInput"
      ref="imageInput"
      accept="image/*"
      style="display: none"
      @change="handleImageChange"
    />

    <!-- 編集フォーム -->
    <form class="recipe-form" @submit.prevent="submit">
      <h2>Edit Recipe</h2>

      <label>料理名</label>
      <input v-model="form.title" type="text" class="recipe-title" />

      <label>ジャンル</label>
      <input v-model="form.genre" type="text" class="recipe-title" />

      <label>人数</label>
      <select v-model="form.servings" class="servings-input">
        <option value="">選択してください</option>
        <option value="1人分">1人分</option>
        <option value="2人分">2人分</option>
        <option value="3人分">3人分</option>
        <option value="4人分">4人分</option>
        <option value="5人以上">5人以上</option>
      </select>

      <label>材料</label>
      <div id="ingredients">
        <div
          class="ingredient-row"
          v-for="(ingredient, index) in form.ingredients"
          :key="index"
        >
          <input
            v-model="ingredient.name"
            type="text"
            class="ingredient-name"
            placeholder="材料名"
          />
          <input
            v-model="ingredient.qty"
            type="text"
            class="ingredient-qty"
            placeholder="分量"
          />
        </div>
      </div>

      <label>作り方</label>
      <textarea
        v-model="form.body"
        class="auto-resize"
        ref="textarea"
        @input="autoResize"
      ></textarea>

      <button type="submit">投稿する</button>
    </form>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

// 初期値のモック（API連携前提）
const form = reactive({
  title: 'サンプルレシピ',
  genre: '和食',
  servings: '2人分',
  ingredients: [
    { name: '玉ねぎ', qty: '1個' },
    { name: '', qty: '' }, // 空欄を最初から1行用意
  ],
  body: 'ここに作り方を記入してください。',
})

const previewUrl = ref('/storage/sample.jpg') // 画像ありの場合、ここに初期URL
const imageInput = ref(null)
const textarea = ref(null)

// 画像クリック → input起動
const selectImage = () => {
  imageInput.value?.click()
}

// プレビュー
const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target.result
  }
  reader.readAsDataURL(file)
}

// 材料の自動追加
watch(
  () => form.ingredients,
  () => {
    const last = form.ingredients[form.ingredients.length - 1]
    if (last.name || last.qty) {
      form.ingredients.push({ name: '', qty: '' })
    }
  },
  { deep: true }
)

// 作り方textareaの自動リサイズ
const autoResize = () => {
  if (!textarea.value) return
  textarea.value.style.height = 'auto'
  textarea.value.style.height = Math.max(textarea.value.scrollHeight, 80) + 'px'
}

onMounted(() => {
  autoResize()
})

// フォーム送信（仮処理）
const submit = () => {
  console.log('送信内容:', form)
  alert('送信処理（API接続予定）')
}
</script>

<style scoped>
@import '@/assets/css/admin/recipes/create.css';

/* フォーカス時の青枠を消す */
input:focus,
select:focus,
textarea:focus {
  outline: none;
  box-shadow: none;
  border-color: #ccc;
}
</style>
