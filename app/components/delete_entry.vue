<template>
    <UModal v-model:open="isOpen">
        <template #content>
            <UCard class="divide-y divide-gray-100 bg-slate-200">
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold leading-6 text-red-600 uppercase">Confirm Deletion</h3>
                        <UButton color="error" variant="ghost" icon="i-lucide-x" class="-my-1" @click="isOpen = false" />
                    </div>
                </template>

                <div class="py-4 space-y-4 text-center">
                    <UIcon name="i-lucide-triangle-alert" class="w-12 h-12 text-red-500 mx-auto" />
                    <p class="text-slate-700 font-medium">
                        Are you sure you want to permanently delete this transaction?
                    </p>
                    
                    <div v-if="entry" class="bg-white p-3 rounded-md border border-gray-200 shadow-sm inline-block mx-auto text-left min-w-50">
                        <div class="text-xs text-gray-500 font-bold uppercase mb-1">{{ entry.CATEGORY?.c_name || 'Transaction' }}</div>
                        <div class="font-bold text-lg" :class="entry.e_type?.toUpperCase() === 'I' ? 'text-green-600' : 'text-red-600'">
                            {{ entry.e_type?.toUpperCase() === 'E' ? '- ₱' : '+ ₱' }} {{ Number(entry.e_amount).toFixed(2) }}
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <UButton block color="neutral" variant="solid" size="lg" class="flex-1 font-bold uppercase" @click="isOpen = false">
                        Cancel
                    </UButton>
                    <UButton block color="error" size="lg" :loading="loading" class="flex-1 font-bold uppercase text-white" @click="executeDelete">
                        Delete
                    </UButton>
                </div>
            </UCard>
        </template>
    </UModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue'

    const props = defineProps(['entry'])
    const emit = defineEmits(['deleted'])
    const isOpen = defineModel('open', { type: Boolean, default: false })
    
    const supabase = useSupabaseClient<any>()
    const toast = useToast()
    const loading = ref(false)

    async function executeDelete() {
        if (!props.entry?.entry_id) return

        loading.value = true

        const { data: { user } } = await supabase.auth.getUser()
        const { data: logData } = await supabase.from('LOG').select('log_id').eq('user_id', user?.id).single()
        const { error } = await supabase
            .from('ENTRY')
            .delete()
            .eq('entry_id', props.entry.entry_id)
        if (!error && logData) await updateLogTotals(logData.log_id)

        loading.value = false

        if (error) {
            console.error(error)
            toast.add({
                title: "Error",
                description: "Could not delete the transaction.",
                color: 'neutral',
                ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
        }
        else {
            toast.add({
                title: "Transaction Deleted",
                description: "The entry has been successfully removed.",
                color: 'neutral',
                ui: {root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white'},
                close: {class: 'text-white'}
            })
            isOpen.value = false
            emit('deleted') 
        }
    }

    async function updateLogTotals(logId: string) {
        const { data: allEntries } = await supabase
            .from('ENTRY')
            .select('e_type, e_amount')
            .eq('log_id', logId)

        if (!allEntries) return

        let income = 0
        let expense = 0

        allEntries.forEach((entry: any) => {
            if (entry.e_type?.toLowerCase() === 'i') income += Number(entry.e_amount)
            if (entry.e_type?.toLowerCase() === 'e') expense += Number(entry.e_amount)
        })
        const balance = income - expense

        await supabase
            .from('LOG')
            .update({
                total_income: income,
                total_expense: expense,
                total_balance: balance
            })
            .eq('log_id', logId)
    }
</script>