<template>
    <div></div>
</template>

<script setup>
const { isAdmin, initAuth } = useAuth()

onMounted(async () => {
    await initAuth()

    // 管理者としてログイン済みなら /admin/dashboard にリダイレクト
    if (isAdmin.value) {
        await navigateTo('/admin/dashboard')
    } else {
        // 管理者でない場合は /admin/login にリダイレクト
        await navigateTo('/admin/login')
    }
})
</script>