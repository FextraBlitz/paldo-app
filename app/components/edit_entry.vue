<template>
    <UModal v-model:open="isOpen" description="Edit transaction details">
        <template #content>
            <UCard class="divide-y divide-gray-100 bg-slate-200">
                <template #header>
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold leading-6 text-gray-900 uppercase">Edit Transaction</h3>
                    <UButton color="error" variant="ghost" icon="i-lucide-x" class="-my-1" @click="isOpen = false" />
                </div>
                </template>

                <UForm @submit.prevent="updateEntry" class="space-y-4">
                    <div class="flex gap-2">
                        <UButton 
                            :class="editForm.e_type === 'e' ? 'flex-1 justify-center font-bold uppercase text-white' : 'flex-1 justify-center font-bold uppercase'" 
                            :variant="editForm.e_type === 'e' ? 'solid' : 'outline'" 
                            color="error" 
                            @click="editForm.e_type = 'e'"
                        >
                            Expense
                        </UButton>
                        <UButton 
                            :class="editForm.e_type === 'i' ? 'flex-1 justify-center font-bold uppercase text-white' : 'flex-1 justify-center font-bold uppercase'" 
                            :variant="editForm.e_type === 'i' ? 'solid' : 'outline'" 
                            color="success" 
                            @click="editForm.e_type = 'i'"
                        >
                            Income
                        </UButton>
                    </div>

                    <UFormField class="font-bold w-full">
                        <template #label><span class="font-bold text-slate-700">Amount</span></template>
                        <UInput v-model="editForm.e_amount" type="number" step="0.01" placeholder="0.00" size="lg" color="info" class="w-full">
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
                                :class="editForm.category_id === cat.category_id ? 'bg-blue-50 ring-2 ring-blue-500 scale-105 shadow-sm' : 'hover:bg-gray-50'"
                                @click="editForm.category_id = cat.category_id"
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
                        <UInput v-model="editForm.e_date" type="datetime-local" size="lg" color="info" class="w-full"/>
                    </UFormField>

                    <UButton type="submit" block color="info" size="lg" :loading="loading" class="mt-6 font-bold uppercase text-white">
                        Save Changes
                    </UButton>
                </UForm>
            </UCard>
        </template>
    </UModal>
</template>

<script setup lang="ts">
    import { ref, watch, onMounted } from 'vue'
    import { format } from 'date-fns'

    const props = defineProps(['entry'])
    const emit = defineEmits(['updated'])
    const isOpen = defineModel('open', { type: Boolean, default: false })
    
    const supabase = useSupabaseClient<any>()
    const toast = useToast()
    const loading = ref(false)
    const categories = ref<any[]>([])

    const editForm = ref({
        entry_id: '',
        e_type: 'e',
        e_amount: '',
        e_date: '',
        category_id: null as string | null
    })

    watch(isOpen, (newVal) => {
        if (newVal && props.entry) {
            editForm.value = {
                entry_id: props.entry.entry_id,
                e_type: props.entry.e_type,
                e_amount: props.entry.e_amount,
                e_date: format(new Date(props.entry.e_date), "yyyy-MM-dd'T'HH:mm"),
                category_id: props.entry.category_id
            }
        }
    })

    onMounted(async () => {
        const { data: { user } } = await supabase.auth.getUser()
        if (!user) return

        const { data: logData } = await supabase.from('LOG').select('log_id').eq('user_id', user.id).single()

        if (logData) {
            const { data: cats } = await supabase
                .from('CATEGORY')
                .select('category_id, c_name, c_color, c_icon')
                .eq('log_id', logData.log_id)
                .order('c_name', { ascending: true })

            if (cats) {
                categories.value = cats
            }
        }
    })

    async function updateEntry() {
        if (!editForm.value.e_amount || !editForm.value.category_id) {
            toast.add({
                title: 'Wait!',
                description: 'Please ensure all fields are filled.',
                color: 'neutral',
                ui: { root: 'bg-red-500 border-2 border-red-900', description: 'text-white' },
                close: {class: 'text-white'}
            })
            return
        }

        loading.value = true

        const { error } = await supabase
            .from('ENTRY')
            .update({
                e_type: editForm.value.e_type,
                e_amount: parseFloat(editForm.value.e_amount),
                category_id: editForm.value.category_id,
                e_date: `${editForm.value.e_date}:00` 
            })
            .eq('entry_id', editForm.value.entry_id)

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
                description: 'Entry updated!',
                color: 'neutral',
                ui: {root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
            isOpen.value = false
            emit('updated')  
        }
    }
</script>