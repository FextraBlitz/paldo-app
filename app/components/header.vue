<template>
    <header class="bg-white border-b px-4 py-3 flex items-center justify-between sticky top-0 z-10">
        <template v-if="!isSearchOpen">
            <img src="~/assets/logo.png" alt="Paldo Logo" class="h-8" />
            <UButton variant="ghost" icon="i-lucide-search" color="error" @click="isSearchOpen = true" />
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

    function closeSearch() {
        searchQuery.value = '' 
        isSearchOpen.value = false
    }
</script>

<style scoped>

</style>