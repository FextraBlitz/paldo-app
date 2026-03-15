<template>
    <UModal v-model:open="isOpen" description="Enter details for a new transaction">
        <div class="fixed bottom-32 right-4 z-20 text-center font-bold">
            <UButton
                icon="i-lucide-plus"
                size="xl"
                color="error"
                class="rounded-full w-14 h-14 border-4 border-red-900 bg-red text-white flex items-center justify-center p-0"
            />
        </div>

        <template #content>
            <UCard class="divide-y divide-gray-100 bg-slate-200">
                <template #header>
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold leading-6 text-gray-900 uppercase">New Transaction</h3>
                    <UButton color="error" variant="ghost" icon="i-lucide-x" class="-my-1" @click="isOpen = false" />
                </div>
                </template>

                <UForm @submit.prevent="addEntry" class="space-y-4">
                    <div class="flex gap-2">
                        <UButton 
                            :class="newEntry.e_type === 'e' ? 'flex-1 justify-center font-bold uppercase text-white' : 'flex-1 justify-center font-bold uppercase'" 
                            :variant="newEntry.e_type === 'e' ? 'solid' : 'outline'" 
                            color="error" 
                            @click="newEntry.e_type = 'e'"
                        >
                            Expense
                        </UButton>
                        <UButton 
                            :class="newEntry.e_type === 'i' ? 'flex-1 justify-center font-bold uppercase text-white' : 'flex-1 justify-center font-bold uppercase'" 
                            :variant="newEntry.e_type === 'i' ? 'solid' : 'outline'" 
                            color="success" 
                            @click="newEntry.e_type = 'i'"
                        >
                            Income
                        </UButton>
                    </div>

                    <UFormField class="font-bold w-full">
                        <template #label><span class="font-bold text-slate-700">Amount</span></template>
                        <UInput v-model="newEntry.e_amount" type="number" step="0.01" placeholder="0.00" size="lg" color="info" class="w-full">
                            <template #leading>
                                <span class="text-gray-500 font-medium">₱</span>
                            </template>
                        </UInput>
                    </UFormField>

                    <UFormField class="font-bold w-full">
                        <template #label><span class="font-bold text-slate-700">Category</span></template>
                        <div class="grid grid-cols-4 gap-3 items-start mt-1 h-42.5 overflow-y-auto p-3 bg-white rounded-md border border-gray-200 shadow-inner">
                            <button
                                v-for="cat in categories"
                                :key="cat.category_id"
                                type="button"
                                class="flex flex-col items-center justify-start gap-1 p-2 rounded-xl transition-all duration-150"
                                :class="newEntry.category_id === cat.category_id ? 'bg-blue-50 ring-2 ring-blue-500 scale-105 shadow-sm' : 'hover:bg-gray-50'"
                                @click="newEntry.category_id = cat.category_id"
                            >
                                <div 
                                    class="flex items-center justify-center w-8 h-8 rounded-full shadow-sm"
                                    :style="{ backgroundColor: cat.c_color || 'slategray' }"
                                >
                                    <UIcon :name="cat.c_icon || 'i-game-icons-two-coins'" class="w-7.5 h-7.5 text-white" />
                                </div>
                                
                                <span class="text-[10px] font-bold text-slate-700 text-center leading-tight truncate w-full">
                                    {{ cat.c_name }}
                                </span>
                            </button>
                        </div>
                    </UFormField>

                    <UFormField class="font-bold w-full">
                        <template #label><span class="font-bold text-slate-700">Date</span></template>
                        <UInput v-model="newEntry.e_date" type="datetime-local" size="lg" color="info" class="w-full"/>
                    </UFormField>

                    <UButton type="submit" block color="info" size="lg" :loading="loading" class="mt-6 font-bold uppercase text-white">
                        Save Entry
                    </UButton>
                </UForm>
            </UCard>
        </template>
    </UModal>
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue'
    import { format } from 'date-fns'

    const emit = defineEmits(['created'])
    const supabase = useSupabaseClient<any>()
    const toast = useToast()
    const isOpen = ref(false)
    const loading = ref(false)
    const categories = ref<any[]>([])

    const newEntry = ref({
        e_type: 'e',
        e_amount: '',
        e_date: format(new Date(), "yyyy-MM-dd'T'HH:mm"),
        category_id: null 
    })

    onMounted(async () => {
        const { data: { user } } = await supabase.auth.getUser()
        if (!user) return

        const { data: logData } = await supabase
            .from('LOG')
            .select('log_id')
            .eq('user_id', user.id)
            .single()

        if (logData) {
            const { data: cats } = await supabase
                .from('CATEGORY')
                .select('category_id, c_name, c_color, c_icon')
                .eq('log_id', logData.log_id)
                .order('c_name', { ascending: true })

            if (cats) {
                categories.value = cats
                if (categories.value.length > 0) {
                    newEntry.value.category_id = categories.value[0].category_id // <-- Added .value!
                }
            }
        }
    })

    async function addEntry() {
        if (!newEntry.value.e_amount) {
            toast.add({
                title: 'Wait!',
                description: 'Please enter an amount.',
                color: 'neutral',
                ui: {
                    root: 'bg-red-500 border-2 border-red-900',
                    description: 'text-white',
                },
                close: {class: 'text-white'}
            })
            return
        }
        if (!newEntry.value.category_id) {
            toast.add({
                title: 'Wait!',
                description: 'Please select a category.',
                color: 'neutral',
                ui: {
                    root: 'bg-red-500 border-2 border-red-900',
                    description: 'text-white',
                },
                close: {class: 'text-white'}
            })
            return
        }

        const { data: { user }, error: authError } = await supabase.auth.getUser()
        loading.value = true

        const {data: logData, error: logError } = await supabase
            .from('LOG')
            .select('log_id')
            .eq('user_id', user?.id)
            .single()

        if (logError || !logData) {
            toast.add({
                title: 'Error',
                description: 'Could not find your log.',
                color: 'neutral',
                ui: {
                    root: 'bg-red-500 border-2 border-red-900',
                    description: 'text-white',
                },
                close: {class: 'text-white'}
            })
            loading.value = false
            return
        }

        const { error } = await supabase
            .from('ENTRY')
            .insert({
                e_type: newEntry.value.e_type,
                e_amount: parseFloat(newEntry.value.e_amount),
                log_id: logData.log_id,
                category_id: newEntry.value.category_id,
                e_date: new Date(newEntry.value.e_date).toISOString() 
            })

        loading.value = false

        if (error) {
            console.error(error)
            toast.add({
                title: 'Database Error',
                description: error.message,
                color: 'neutral',
                ui: {
                    root: 'bg-red-500 border-2 border-red-900',
                    description: 'text-white',
                },
                close: {class: 'text-white'}
            })
        }
        else {
            toast.add({
                title: 'Success',
                description: 'Entry added!',
                color: 'neutral',
                ui: {
                    root: 'bg-blue-500 border-2 border-blue-900',
                    description: 'text-white',
                },
                close: {class: 'text-white'}
            })
            isOpen.value = false
            emit('created')  
            newEntry.value.e_amount = ''
            if (categories.value.length > 0) newEntry.value.category_id = categories.value[0].category_id
        }
    }
</script>