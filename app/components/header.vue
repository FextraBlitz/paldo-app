<template>
    <header class="inset-x-0 max-w-screen w-full bg-white border-b px-4 py-3 flex items-center justify-between sticky top-0 z-10">
        <template v-if="!isSearchOpen">
            <img src="~/assets/logo.png" alt="Paldo Logo" class="h-8" />
            
            <UButton 
                v-if="isSummaryPage" 
                variant="ghost" 
                icon="i-lucide-search" 
                color="error" 
                @click="isSearchOpen = true" 
            />
            <div v-else class="w-8"></div>
        </template>

        <template v-else>
            <UInput 
                v-model="searchQuery" 
                icon="i-lucide-search" 
                placeholder="Search category..." 
                color="error" 
                variant="outline"
                class="w-full"
                autofocus
                :ui="{ trailing: 'pointer-events-auto pr-3' }"
            >
                <template #trailing>
                    <UButton 
                        color="error" 
                        variant="link" 
                        icon="i-lucide-x" 
                        :padded="false" 
                        @click="closeSearch" 
                    />
                </template>
            </UInput>
        </template>
    </header>
</template>

<script setup lang="ts">
    import { ref } from 'vue'
    const searchQuery = defineModel<string>({ default: '' })
    const isSearchOpen = ref(false)
    const route = useRoute()

    const isSummaryPage = computed(() => route.path === '/' || route.path === '/summary')

    watch(() => route.path, () => {
        closeSearch()
    })

    function closeSearch() {
        searchQuery.value = '' 
        isSearchOpen.value = false
    }
</script>

<style scoped>

</style>