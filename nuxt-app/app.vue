<template>
    <div>
        <NuxtLayout>
            <NuxtPage />
        </NuxtLayout>
    </div>
</template>

<script setup>
  // 認証の初期化
const { initAuth } = useAuth()

    onMounted(() => {
        initAuth()  // 認証状態の監視開始
    })
</script>