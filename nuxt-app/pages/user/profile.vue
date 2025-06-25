<template>
  <div class="profile-container">
    <h2>プロフィール編集</h2>

    <form @submit.prevent="saveProfile">
      <!-- アイコン -->
      <div class="avatar-section">
        <img 
          v-if="user.avatar" 
          :src="user.avatar" 
          alt="アイコン" 
          class="avatar-img"
        >
        <div 
          v-else 
          class="avatar-icon"
        >
          <i class="fas fa-user material-symbols-outlined"></i>
        </div>
        
        <label for="avatar-upload" class="file-upload-label">
          <input 
            id="avatar-upload"
            type="file" 
            name="avatar" 
            accept="image/*"
            @change="handleAvatarChange"
            style="display: none;"
          >
          <span class="upload-button">画像を選択</span>
        </label>
      </div>

      <!-- 名前 -->
      <label for="username">ユーザーネーム</label>
      <input 
        id="username"
        type="text" 
        name="name" 
        v-model="user.name"
        required
      >

      <!-- 保存ボタン -->
      <button type="submit" class="save-button" :disabled="isLoading">
        {{ isLoading ? '保存中...' : '保存する' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useHead } from '#app'

// Head設定
useHead({
  title: 'プロフィール編集',
  link: [
    {
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap'
    },
    {
      rel: 'stylesheet',
      href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'
    }
  ]
})

// データ定義
const isLoading = ref(false)

// ユーザーデータ（実際はAPIから取得）
const user = reactive({
  id: 1,
  name: 'ユーザー名',
  avatar: null // 画像がある場合は画像URLを設定
})

// アバター画像の変更処理
const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    // プレビュー用にファイルを読み込み
    const reader = new FileReader()
    reader.onload = (e) => {
      user.avatar = e.target.result
    }
    reader.readAsDataURL(file)
    
    console.log('選択されたファイル:', file.name)
    // 実際のファイルアップロード処理
    // uploadAvatar(file)
  }
}

// プロフィール保存処理
const saveProfile = async () => {
  isLoading.value = true
  
  try {
    console.log('プロフィール保存:', {
      name: user.name,
      avatar: user.avatar ? '画像あり' : '画像なし'
    })
    
    // 実際のAPI呼び出し
    // const response = await $fetch('/api/profile', {
    //   method: 'PUT',
    //   body: {
    //     name: user.name,
    //     avatar: avatarFile
    //   }
    // })
    
    // 保存成功の処理
    await new Promise(resolve => setTimeout(resolve, 1000)) // デモ用の遅延
    alert('プロフィールを保存しました！')
    
  } catch (error) {
    console.error('保存エラー:', error)
    alert('保存に失敗しました')
  } finally {
    isLoading.value = false
  }
}

// ページ読み込み時にユーザーデータを取得
onMounted(async () => {
  console.log('プロフィールページ読み込み')
  // 実際のAPI呼び出し
  // const userData = await $fetch('/api/user/profile')
  // Object.assign(user, userData)
})
</script>

<style>
@import "@/assets/css/common.css";
@import "@/assets/css/user/profile.css";


</style>