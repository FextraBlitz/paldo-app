<script setup lang="ts">
        import { ref, onMounted } from 'vue'
        import { useRouter } from 'vue-router'
        import SplashScreen from '~/components/splash.vue'
        const loading_states = useLoadingStates()

        const router = useRouter()
        const isSplashLoading = ref(false)

        //   onMounted(() => {
        //       const hasVisitedBefore = localStorage.getItem('hasVisited')
        //       const isLoggedIn = sessionStorage.getItem('isLoggedIn')

        //       if (hasVisitedBefore) {
        //           setTimeout(() => {
        //               isSplashLoading.value = false
        //               router.push('/summary')
        //           }, 3000)
        //       }
        //       else {
        //           localStorage.setItem('hasVisited', 'true')
        //           setTimeout(() => {
        //               isSplashLoading.value = false
        //               router.push('/login')
        //           }, 3000)
        //       }
        //     })
    import { computed } from 'vue'
    const route = useRoute()

    const authPaths = ['/', '/login', '/register', '/forgot-password', '/forgot_password', '/update-password', '/update_password']
    const noHeaderPaths = ['/profile', '/edit-profile', '/edit_profile']

    const showHeader = computed(() => { return !authPaths.includes(route.path) && !noHeaderPaths.includes(route.path) })
    const showFooter = computed(() => { return !authPaths.includes(route.path) })
</script>

<template>
    <ClientOnly>
        <UApp :toaster="{ position: 'bottom-center' }">
            <SplashScreen v-if="false" />
            <!-- <NuxtPage v-else /> -->
            <div class="min-h-screen flex flex-col items-center bg-white">
                
                <Header v-if="showHeader" />
                
                <div class="mx-auto w-full lg:w-[50%] relative min-h-screen border-x border-slate-300">
                    <NuxtPage />
                </div>
                
                <Footer v-if="showFooter" />
                
            </div>
        </UApp>
    </ClientOnly>
</template>