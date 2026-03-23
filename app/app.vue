<script setup lang="ts">
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