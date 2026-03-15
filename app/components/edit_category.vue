<template>
    <UModal v-model:open="isOpen">
        <template #content>
            <UCard class="divide-y divide-slate-700 bg-slate-100 ring-0">
                <template #header>
                    <div class="flex items-center justify-between bg-slate-100">
                        <h3 class="text-base font-bold leading-6 text-gray-900 uppercase">Edit Category</h3>
                        <UButton color="info" variant="ghost" icon="i-lucide-x" class="-my-1" @click="isOpen = false" />
                    </div>
                </template>

                <form @submit.prevent="updateCategory" class="space-y-4">
                
                <UFormField class="w-full">
                    <template #label><span class="font-bold text-slate-700">Category Name</span></template>
                    <UInput v-model="editForm.c_name" color="info"placeholder="e.g., Groceries, Rent, Salary" size="lg" class="w-full" />
                </UFormField>

                <UFormField>
                    <template #label><span class="font-bold text-slate-700">Color</span></template>
                    <div class="flex flex-wrap gap-3 mt-2">
                    <button
                        v-for="color in colors"
                        :key="color.value"
                        type="button"
                        class="w-8 h-8 rounded-md transition-all duration-150 border border-black/10 shadow-sm"
                        :style="{ backgroundColor: color.value }"
                        :class="editForm.c_color === color.value ? 'ring-2 ring-offset-2 ring-slate-800 scale-110' : 'hover:scale-105'"
                        @click="editForm.c_color = color.value"
                    ></button>
                    </div>
                </UFormField>

                <UFormField class="w-full">
                    <template #label><span class="font-bold text-slate-700 w-full">Icon</span></template>
                    <div class="space-y-3 mt-1 w-full">
                        <UInput v-model="iconSearch" icon="i-lucide-search" color="info" placeholder="Search icons..." size="md" class="w-full" />
                        
                        <div class="h-42.5 overflow-y-auto grid grid-cols-6 items-start gap-2 p-2 border border-gray-200 rounded-md bg-white shadow-inner relative">
                            <div v-if="loadingIcons" class="col-span-6 flex items-center justify-center py-8 text-xs font-bold text-gray-500 uppercase tracking-widest">
                                Loading Icons...
                            </div>

                            <button
                                v-for="icon in filteredIcons"
                                :key="icon.value"
                                type="button"
                                class="flex items-center justify-center p-2 rounded-md transition-all duration-150 border"
                                :class="editForm.c_icon === icon.value ? 'border-slate-800 bg-slate-200 scale-110' : 'border-transparent hover:bg-gray-100'"
                                @click="editForm.c_icon = icon.value"
                                :title="icon.label"
                            >
                            <UIcon :name="icon.value" class="w-8 h-8 text-slate-800" />
                            </button>
                            
                            <div v-if="!loadingIcons && filteredIcons.length === 0" class="col-span-6 text-center text-xs text-gray-400 py-8">
                                No icons match your search.
                            </div>
                        </div>
                    </div>
                </UFormField>

                <!-- Live Preview -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200 flex flex-col gap-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Live Preview</span>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full" :style="{ backgroundColor: editForm.c_color }">
                            <UIcon :name="editForm.c_icon" class="w-7.5 h-7.5 text-white" />
                        </div>
                        <span class="font-medium text-slate-800">{{ editForm.c_name || 'Category Name' }}</span>
                    </div>
                </div>

                <UButton type="submit" block color="info" size="lg" :loading="loading" class="mt-6 font-bold text-white uppercase">
                    Save Changes
                </UButton>
                </form>
            </UCard>
        </template>
    </UModal>
</template>

<script setup lang="ts">
    import { ref, computed, watch, onMounted } from 'vue'

    const props = defineProps(['category'])
    const emit = defineEmits(['updated'])
    const isOpen = defineModel('open', { type: Boolean, default: false })

    const supabase = useSupabaseClient<any>()
    const toast = useToast()

    const loading = ref(false)
    const iconSearch = ref('')
    const allIcons = ref<any[]>([])
    const loadingIcons = ref(true)

    const editForm = ref({
        category_id: '',
        c_name: '',
        c_color: '',
        c_icon: ''
    })

    watch(isOpen, (newVal) => {
        if (newVal && props.category) {
            editForm.value = {
                category_id: props.category.category_id,
                c_name: props.category.c_name,
                c_color: props.category.c_color,
                c_icon: props.category.c_icon
            }
            iconSearch.value = ''
        }
    })

    const colors = [
        { value: 'slategray' },
        { value: 'black' },
        { value: 'gray' },
        { value: 'silver' },
        { value: 'tan' },
        { value: 'sienna' },
        { value: 'brown' },
        { value: 'crimson' },
        { value: 'red' },
        { value: 'orangered' },
        { value: 'darkorange' },
        { value: 'orange' },
        { value: 'gold' },
        { value: 'yellowgreen' },
        { value: 'chartreuse' },
        { value: 'forestgreen' },
        { value: 'seagreen' },
        { value: 'teal' },
        { value: 'darkturquoise' },
        { value: 'steelblue' },
        { value: 'dodgerblue' },
        { value: 'royalblue' },
        { value: 'blue' },
        { value: 'midnightblue' },
        { value: 'indigo' },
        { value: 'blueviolet' },
        { value: 'slateblue' },
        { value: 'rebeccapurple' },
        { value: 'purple' },
        { value: 'mediumvioletred' },
        { value: 'hotpink' },
        { value: 'orchid' },
    ]

    onMounted(async () => {
        try {
            const res = await fetch('https://api.iconify.design/collection?prefix=game-icons')
            const data = await res.json()
            let rawIcons: string[] = []
            if (data.uncategorized) rawIcons.push(...data.uncategorized)
            if (data.categories) {
                for (const category in data.categories) {
                    rawIcons.push(...data.categories[category])
                }
            }
            allIcons.value = [...new Set(rawIcons)].map(name => ({
                label: name.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase()), 
                value: `i-game-icons-${name}`, 
                keywords: name.replace(/-/g, ' ') 
            }))
        } catch (e) {
            console.error('Failed to load icons', e)
        } finally {
            loadingIcons.value = false
        }
    })

    const filteredIcons = computed(() => {
        let results = allIcons.value
        if (iconSearch.value) {
            const query = iconSearch.value.toLowerCase()
            results = allIcons.value.filter(icon => 
                icon.label.toLowerCase().includes(query) || 
                icon.keywords.toLowerCase().includes(query)
            )
        }
        return results.slice(0, 60) 
    })

    async function updateCategory() {
        if (!editForm.value.c_name) {
            toast.add({
                title: 'Missing Information!',
                description: 'Please enter a category name.',
                color: 'neutral',
                ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
            return
        }

        loading.value = true

        const { error } = await supabase
            .from('CATEGORY')
            .update({
                c_name: editForm.value.c_name,
                c_color: editForm.value.c_color,
                c_icon: editForm.value.c_icon
            })
            .eq('category_id', editForm.value.category_id)

        loading.value = false

        if (error) {
            console.error(error)
            toast.add({
                title: 'Database Error',
                description: error.message,
                color: 'neutral',
                ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
        } else {
            toast.add({
                title: 'Success',
                description: 'Category updated!',
                color: 'neutral',
                ui: {root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
            isOpen.value = false 
            emit('updated') 
        }
    }
</script>