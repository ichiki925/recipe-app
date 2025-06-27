<template>
  <div class="recipe-create-container">
    <!-- プレビューエリア -->
    <div class="image-preview" @click="triggerImageInput">
      <span v-if="!imagePreview" id="preview-text">No Image</span>
      <img
        v-if="imagePreview"
        :src="imagePreview"
        alt="プレビュー"
        id="preview-image"
      />
    </div>
    <input
      type="file"
      ref="imageInput"
      style="display: none"
      accept="image/*"
      @change="previewImage"
    />

    <form class="recipe-form" @submit.prevent="submitRecipe">
      <h2>New Recipe</h2>

      <label>料理名</label>
      <input type="text" v-model="form.title" class="recipe-title" />

      <label>ジャンル</label>
      <input type="text" v-model="form.genre" class="recipe-title" />

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
            type="text"
            v-model="ingredient.name"
            class="ingredient-name"
            placeholder="材料名"
          />
          <input
            type="text"
            v-model="ingredient.qty"
            class="ingredient-qty"
            placeholder="分量"
          />
        </div>
      </div>

      <label>作り方</label>
      <textarea
        v-model="form.body"
        class="auto-resize"
        @input="resizeTextarea"
      ></textarea>

      <button type="submit">投稿する</button>
    </form>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})
import { ref, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = reactive({
  title: '',
  genre: '',
  servings: '',
  ingredients: [{ name: '', qty: '' }],
  body: '',
})

const imageInput = ref(null)
const imagePreview = ref('')

const triggerImageInput = () => {
  imageInput.value?.click()
}

const previewImage = (event) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

watch(
  () => form.ingredients,
  (newIngredients) => {
    const last = newIngredients[newIngredients.length - 1]
    if (last.name || last.qty) {
      form.ingredients.push({ name: '', qty: '' })
    }
  },
  { deep: true }
)

const resizeTextarea = (event) => {
  const textarea = event.target
  textarea.style.height = 'auto'
  textarea.style.height = Math.max(80, textarea.scrollHeight) + 'px'
}

const submitRecipe = () => {
  console.log('投稿データ:', form)
  // 実際の送信処理（APIなど）をここに書く
}
</script>

<style scoped>
@import '@/assets/css/common.css';
@import '@/assets/css/admin/recipes/create.css';
</style>
