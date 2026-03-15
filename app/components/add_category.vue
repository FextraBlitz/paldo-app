<template>
    <UModal v-model:open="isOpen">
        <UButton block color="info" variant="outline" size="lg" class="font-bold border-black text-blue-500 hover:bg-gray-50 uppercase">
            Add New Category
        </UButton>
        
        <template #content>
            <UCard class="divide-y divide-slate-700 bg-slate-100 ring-0">
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold leading-6 text-gray-900 uppercase">
                        New Category
                        </h3>
                        <UButton color="error" variant="ghost" icon="i-lucide-x" class="-my-1" @click="isOpen = false" />
                    </div>
                </template>
                <form @submit.prevent="addCategory" class="space-y-4 w-full">
                    <UFormField class="font-bold text-slate-700">
                        <template #label>
                            <span class="font-bold text-slate-700">Category Name</span>
                        </template>
                        <UInput v-model="newCategory.c_name" color="info" placeholder="e.g., Groceries, Rent, Salary" size="lg" class="w-full" />
                    </UFormField>
                    <!-- Color Selection -->
                    <UFormField>
                        <template #label>
                            <span class="font-bold text-slate-700">Color</span>
                        </template>
                        <div class="flex flex-wrap gap-3 mt-2">
                            <button
                                v-for="color in colors"
                                :key="color.value"
                                type="button"
                                class="w-8 h-8 rounded-md transition-all duration-150 border border-black/10 shadow-sm"
                                :style="{ backgroundColor: color.value }"
                                :class="{
                                    'ring-2 ring-offset-2 ring-slate-800 scale-110': newCategory.c_color === color.value,
                                    'hover:scale-105': newCategory.c_color !== color.value
                                }"
                                @click="newCategory.c_color = color.value"
                            />
                        </div>
                    </UFormField>
                    <!-- Icon Selection -->
                    <UFormField>
                        <template #label>
                            <span class="font-bold text-slate-700">Icon</span>
                        </template>
                        <div class="space-y-3 mt-1 w-full">
                            <UInput 
                                v-model="iconSearch" 
                                icon="i-lucide-search" 
                                placeholder="Search icons (e.g., food, car, home)..." 
                                color="info"
                                size="md"
                                class="w-full"
                            />
                            <div class="h-42.5 overflow-y-auto grid grid-cols-6 items-start gap-2 p-2 border border-gray-200 rounded-md bg-white shadow-inner relative">
                                <div v-if="loadingIcons" class="col-span-6 flex items-center justify-center py-8 text-xs font-bold text-gray-500 uppercase tracking-widest">
                                    Loading 1,400+ Icons...
                                </div>

                                <button
                                    v-for="icon in filteredIcons"
                                    :key="icon.value"
                                    type="button"
                                    class="flex items-center justify-center p-2 rounded-md transition-all duration-150 border"
                                    :class="newCategory.c_icon === icon.value ? 'border-slate-800 bg-slate-200 scale-110' : 'border-transparent hover:bg-gray-100'"
                                    @click="newCategory.c_icon = icon.value"
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
                            <div class="flex items-center justify-center w-8 h-8 rounded-full" :style="{ backgroundColor: newCategory.c_color }">
                                <UIcon :name="newCategory.c_icon" class="w-7.5 h-7.5 text-white" />
                            </div>
                            <span class="font-medium text-slate-800">{{ newCategory.c_name || 'Category Name' }}</span>
                        </div>
                    </div>
                    <UButton type="submit" block color="info" size="lg" :loading="loading" class="mt-6 font-bold text-white uppercase">
                        Save Category
                    </UButton>
                </form>
            </UCard>
        </template>
    </UModal>
</template>

<script setup lang="ts">
    import { ref, computed, onMounted } from 'vue'

    const emit = defineEmits(['created'])
    const supabase = useSupabaseClient<any>()
    const toast = useToast()

    const isOpen = ref(false)
    const loading = ref(false)
    const iconSearch = ref('')
    const allIcons = ref<any[]>([])
    const loadingIcons = ref(true)

    const newCategory = ref({
        c_name: '',
        c_color: 'slategray',
        c_icon: 'i-game-icons-two-coins'
    })

    //color list
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
        }
        catch (e) {
            console.error('Failed to load icons', e)
            toast.add({
                title: 'Icon Error',
                description: 'Could not load the full icon library.',
                color: 'neutral',
                ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
        }
        finally {
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

    async function addCategory() {
        if (!newCategory.value.c_name) {
            toast.add({
                title:'Missing information!',
                description: 'Please enter a name for your category.',
                color: 'neutral',
                ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
            return
        }
        loading.value = true
        const { data: { user }, error: authError } = await supabase.auth.getUser()

        if (authError || !user) {
            toast.add({
                title: 'Auth Error',
                description: 'Could not verify your session. Please log in again.',
                color: 'neutral',
                ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
            loading.value = false
            return
        }

        const { data: logData, error: logError } = await supabase
            .from('LOG')
            .select('log_id')
            .eq('user_id', user.id)
            .single()

        if (logError || !logData) {
            toast.add({
                title: 'Error',
                description: 'Could not find your log.',
                color: 'neutral',
                ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
            loading.value = false
            return
        }
        const { error } = await supabase
            .from('CATEGORY')
            .insert({
                c_name: newCategory.value.c_name,
                c_color: newCategory.value.c_color,
                c_icon: newCategory.value.c_icon,
                log_id: logData.log_id
            })

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
        }
        else {
            toast.add({
                title: 'Success',
                description: 'Category created!',
                color: 'neutral',
                ui: {root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
            isOpen.value = false 
            emit('created') 
            newCategory.value = { c_name: '', c_color: 'slategray', c_icon: 'i-lucide-tag' }
        }
    }
</script>