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

<style scoped>
@import "@/assets/css/common.css";

.profile-container {
    width: 400px;
    margin: 50px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.profile-container h2 {
    text-align: center;
    margin-bottom: 20px;
    font-weight: lighter;
    font-size: 20px;
}

.avatar-section {
    text-align: center;
    margin-bottom: 20px;
}

.avatar-img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ccc;
}

.no-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #eee;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    font-size: 48px;
    color: #aaa;
}

.avatar-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-color: #eee;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
}

.upload-button {
    display: inline-block;
    padding: 8px 16px;
    background-color: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
    color: #606060;
    margin: 10px;
}

.upload-button:hover {
    background-color: #e0e0e0;
}


label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
    color: #606060;
}

input[type="file"] {
    padding: 10px 16px;
    font-size: 14px;
    border-radius: 6px;
    cursor: pointer;
    color: #ccc;

}

input[type="text"] {
    width: 100%;
    padding: 12px;
    margin-top: 5px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
}

input[type="text"]:focus {
    outline: none;
}



.save-button {
    width: 100%;
    margin-top: 30px;
    padding: 10px;
    background: #f0f0f0;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    color: #606060;
    cursor: pointer;
}

.save-button:hover:not(:disabled) {
    background: #e0e0e0;
    /* ホバー時に少し濃く */
}

@media (max-width: 480px) {

    /* 全体の背景を白に */
    body {
        background-color: #fff;
    }

    .profile-container {
        width: 100%;
        margin: 0;
        padding: 20px;
        /* ボックススタイルを削除 */
        background: transparent;
        border-radius: 0;
        box-shadow: none;
    }

    .avatar-img,
    .avatar-icon {
        width: 100px;
        height: 100px;
    }

    .avatar-icon {
        font-size: 40px;
    }

    .material-symbols-outlined {
        font-size: 40px;
    }

    .profile-container h2 {
        font-size: 18px;
    }

    .upload-button {
        padding: 6px 12px;
        font-size: 12px;
    }

    input[type="text"] {
        padding: 12px;
        font-size: 16px;
    }

    .save-button {
        padding: 12px;
        font-size: 16px;
    }
}

@media (max-width: 360px) {
    .profile-container {
        padding: 15px;
    }
}


</style>