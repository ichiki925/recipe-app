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

      <!-- エラーメッセージ表示 -->
      <div v-if="errors.length > 0" class="error-messages">
        <div v-for="error in errors" :key="error" class="error-message">
          {{ error }}
        </div>
      </div>

      <!-- 成功メッセージ表示 -->
      <div v-if="successMessage" class="success-message">
        {{ successMessage }}
      </div>

      <label>料理名</label>
      <input type="text" v-model="form.title" class="recipe-title" required />

      <label>ジャンル</label>
      <input type="text" v-model="form.genre" class="recipe-title" />

      <label>人数</label>
      <select v-model="form.servings" class="servings-input" required>
        <option value="">選択してください</option>
        <option value="1人分">1人分</option>
        <option value="2人分">2人分</option>
        <option value="3人分">3人分</option>
        <option value="4人分">4人分</option>
        <option value="5人分以上">5人分以上</option>
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
        v-model="form.instructions"
        class="auto-resize"
        @input="resizeTextarea"
        placeholder="作り方を入力してください"
        required
      ></textarea>

      <button type="submit" :disabled="isSubmitting">
        {{ isSubmitting ? '投稿中...' : '投稿する' }}
      </button>
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
  instructions: '' // bodyからinstructionsに変更
})

const imageInput = ref(null)
const imagePreview = ref('')
const selectedFile = ref(null)
const errors = ref([])
const successMessage = ref('')
const isSubmitting = ref(false)

const triggerImageInput = () => {
  imageInput.value?.click()
}

const previewImage = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const removeIngredient = (index) => {
  if (form.ingredients.length > 1) {
    form.ingredients.splice(index, 1)
  }
}

// 材料入力時の動的追加
watch(
  () => form.ingredients,
  (newIngredients) => {
    const last = newIngredients[newIngredients.length - 1]
    if (last && (last.name || last.qty)) {
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

// 材料を文字列形式に変換
const formatIngredients = () => {
  return form.ingredients
    .filter(ingredient => ingredient.name.trim() || ingredient.qty.trim())
    .map(ingredient => `${ingredient.name.trim()} ${ingredient.qty.trim()}`)
    .join('\n')
}

const submitRecipe = async () => {
  errors.value = []
  successMessage.value = ''
  isSubmitting.value = true

  try {
    // バリデーション
    if (!form.title.trim()) {
      errors.value.push('料理名は必須です')
    }
    if (!form.servings) {
      errors.value.push('人数を選択してください')
    }
    if (!form.instructions.trim()) {
      errors.value.push('作り方は必須です')
    }

    const ingredientsText = formatIngredients()
    if (!ingredientsText) {
      errors.value.push('材料は必須です')
    }

    if (errors.value.length > 0) {
      isSubmitting.value = false
      return
    }

    // FormDataを作成
    const formData = new FormData()
    formData.append('title', form.title)
    formData.append('genre', form.genre || '')
    formData.append('servings', form.servings)
    formData.append('ingredients', ingredientsText)
    formData.append('instructions', form.instructions)

    if (selectedFile.value) {
      formData.append('image', selectedFile.value)
    }

    // API送信
    const response = await $fetch('/api/admin/recipes', {
      method: 'POST',
      body: formData
    })

    successMessage.value = 'レシピが投稿されました'
    
    // フォームリセット
    Object.assign(form, {
      title: '',
      genre: '',
      servings: '',
      ingredients: [{ name: '', qty: '' }],
      instructions: ''
    })
    
    imagePreview.value = ''
    selectedFile.value = null
    
    // 管理画面のレシピ一覧にリダイレクト
    setTimeout(() => {
      router.push('/admin/recipes')
    }, 1500)

  } catch (error) {
    console.error('投稿エラー:', error)
    
    if (error.data?.errors) {
      // Laravel バリデーションエラー
      errors.value = Object.values(error.data.errors).flat()
    } else {
      errors.value = ['投稿に失敗しました。もう一度お試しください。']
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
@import '@/assets/css/common.css';

body {
    margin: 0;
    font-family: sans-serif;
}

/* 全体の2カラムレイアウト */
.recipe-create-container {
    display: flex;
    padding: 40px;
    gap: 40px;
    justify-content: center;
    align-items: flex-start;
}

/* 左側の画像部分 */
.image-preview {
    width: 300px;
    height: 300px;
    background-color: #f0f0f0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 80px;
    cursor: pointer;
    overflow: hidden;
    position: relative;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    color: #999;
    font-size: 18px;
}

/* 右側の入力フォーム */
.recipe-form {
    width: 400px;
}

.recipe-form h2 {
    margin-bottom: 20px;
    text-align: center;
    font-family: cursive;
}

.recipe-form label {
    display: block;
    font-weight: bold;
    margin-top: 15px;
    margin-bottom: 5px;
}

.recipe-title {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #aaa;
    border-radius: 6px;
    box-sizing: border-box;
}

.recipe-title:focus {
    outline: none;
    border: 1px solid #aaa;
}

/* 材料名と分量を横並び */
.recipe-form .ingredient-row {
    display: flex;
    gap: 0px;
    margin-bottom: 10px;
}

/* 材料名入力欄 */
.recipe-form .ingredient-name {
    flex: 2;
    border: 1px solid #aaa !important;
    border-right: none !important;
    border-radius: 6px 0 0 6px !important;
    padding: 10px !important;
    box-sizing: border-box;
}

.recipe-form .ingredient-name:focus {
    outline: none;
    border: 1px solid #aaa !important;
    border-right: none !important;
}

/* 分量入力欄 */
.recipe-form .ingredient-qty {
    flex: 1;
    border: 1px solid #aaa !important;
    border-left: none !important;
    border-radius: 0 6px 6px 0 !important;
    padding: 10px !important;
    box-sizing: border-box;
}

.recipe-form .ingredient-qty:focus {
    outline: none;
    border: 1px solid #aaa !important;
    border-left: none !important;
}

.remove-ingredient:hover {
    background-color: #cc0000;
}

.servings-input {
    width: 150px;
    padding: 8px;
    font-size: 14px;
    margin-bottom: 10px;
    border: 1px solid #aaa;
    border-radius: 6px;
}

.servings-input:focus {
    outline: none;
    border: 1px solid #aaa;
}

.auto-resize {
    width: 100%;
    min-height: 80px;
    resize: none;
    padding: 10px;
    border: 1px solid #aaa;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
    overflow: auto;
}

.auto-resize:focus {
    outline: none;
    border: 1px solid #aaa;
}

.recipe-form button {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    background-color: #ccc;
    border: none;
    font-weight: bold;
    cursor: pointer;
    border-radius: 6px;
}

.recipe-form button:hover:not(:disabled) {
    background-color: #bbb;
}

.recipe-form button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

/* エラーメッセージ */
.error-messages {
    margin-bottom: 20px;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 6px;
    border: 1px solid #f5c6cb;
}

/* 成功メッセージ */
.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 6px;
    border: 1px solid #c3e6cb;
}

input::placeholder,
textarea::placeholder {
    color: #ccc !important;
    opacity: 1 !important;
}

@media screen and (max-width: 768px) {
    .recipe-create-container {
        flex-direction: column;
        align-items: center;
        padding: 20px;
        gap: 20px;
    }

    .image-preview {
        width: 100%;
        max-width: 280px;
        height: 280px;
        margin: 40px auto 0;
    }

    .recipe-form {
        width: 100%;
        max-width: 400px;
    }

    .ingredient-row {
        flex-direction: column;
        gap: 8px;
    }

    .recipe-form .ingredient-name,
    .recipe-form .ingredient-qty {
        border: 1px solid #aaa !important;
        border-radius: 6px !important;
        flex: none;
        margin-bottom: 8px;
    }

    .recipe-form .ingredient-name:focus,
    .recipe-form .ingredient-qty:focus {
        outline: none;
        border: 1px solid #aaa !important;
    }

    .recipe-form .ingredient-qty {
        margin-bottom: 0;
    }

    .servings-input {
        width: 100%;
    }

    .auto-resize {
        min-height: 100px;
    }
}
</style>