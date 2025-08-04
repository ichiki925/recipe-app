export default defineNuxtRouteMiddleware((to) => {
    if (process.client && to.path === '/') {
        const seen = localStorage.getItem('welcome_seen')
        if (!seen) {
            localStorage.setItem('welcome_seen', 'true')
            return navigateTo('/user/welcome')
        }
    }
})