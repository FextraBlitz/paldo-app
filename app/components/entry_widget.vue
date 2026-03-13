<template>
    <UModal v-model:open="isOpen">
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
                    <UButton color="info" variant="ghost" icon="i-lucide-x" class="-my-1" @click="isOpen = false" />
                </div>
                </template>

                <UForm @submit.prevent="addEntry" class="space-y-4">
                    <div class="flex gap-2">
                        <UButton 
                            class="flex-1 justify-center font-bold uppercase" 
                            :variant="newEntry.e_type === 'e' ? 'solid' : 'outline'" 
                            color="error" 
                            @click="newEntry.e_type = 'e'"
                        >
                            Expense
                        </UButton>
                        <UButton 
                            class="flex-1 justify-center font-bold uppercase" 
                            :variant="newEntry.e_type === 'i' ? 'solid' : 'outline'" 
                            color="success" 
                            @click="newEntry.e_type = 'i'"
                        >
                            Income
                        </UButton>
                    </div>

                    <UFormField label="Amount" class="font-bold">
                        <UInput v-model="newEntry.e_amount" type="number" step="0.01" placeholder="0.00" size="lg" color="info">
                            <template #leading>
                                <span class="text-gray-500 font-medium">₱</span>
                            </template>
                        </UInput>
                    </UFormField>

                    <UFormField label="Date" class="font-bold">
                        <UInput v-model="newEntry.e_date" type="datetime-local" size="lg" color="info"/>
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
    import { ref } from 'vue'
    import { format } from 'date-fns'

    const emit = defineEmits(['created'])
    const supabase = useSupabaseClient<any>()
    const toast = useToast()
    const isOpen = ref(false)
    const loading = ref(false)

    const newEntry = ref({
        e_type: 'e',
        e_amount: '',
        e_date: format(new Date(), "yyyy-MM-dd'T'HH:mm"),
        category_id: null 
    })

    async function addEntry() {
        if (!newEntry.value.e_amount) {
            toast.add({ title: 'Wait!', description: 'Please enter an amount.', color: 'error' })
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
            toast.add({ title: 'Error', description: 'Could not find your log.', color: 'error' })
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
            toast.add({ title: 'Database Error', description: error.message, color: 'error' })
        }
        else {
            toast.add({ title: 'Success', description: 'Entry added!', color: 'success' })
            isOpen.value = false
            emit('created')  
            newEntry.value.e_amount = ''
        }
    }
</script>