<template>
    <UModal v-model:open="isOpen" description="Enter details for a new financial goal">
        <template #content>
            <UCard class="divide-y divide-gray-100 bg-slate-200">
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold leading-6 text-gray-900 uppercase">New Goal</h3>
                        <UButton color="error" variant="ghost" icon="i-lucide-x" class="-my-1" @click="isOpen = false" />
                    </div>
                </template>

                <UForm @submit.prevent="saveGoal" class="space-y-4">
                    
                    <UFormField class="font-bold w-full">
                        <template #label><span class="font-bold text-slate-700">Goal Name</span></template>
                        <UInput v-model="newGoal.goal_name" placeholder="e.g., Emergency Fund, New Laptop" size="lg" color="info" class="w-full font-medium" />
                    </UFormField>

                    <UFormField class="font-bold w-full">
                        <template #label><span class="font-bold text-slate-700">Target Amount</span></template>
                        <UInput v-model="newGoal.goal_amount" type="number" step="0.01" placeholder="0.00" size="lg" color="info" class="w-full font-medium">
                            <template #leading>
                                <span class="text-gray-500 font-medium">₱</span>
                            </template>
                        </UInput>
                    </UFormField>

                    <UFormField class="font-bold w-full">
                        <template #label><span class="font-bold text-slate-700">Target Deadline</span></template>
                        <UInput v-model="newGoal.goal_deadline" type="date" size="lg" color="info" class="w-full font-medium"/>
                    </UFormField>

                    <UButton type="submit" block color="info" size="lg" :loading="loading" class="mt-6 font-bold uppercase text-white">
                        Save Goal
                    </UButton>
                </UForm>
            </UCard>
        </template>
    </UModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue'

    const isOpen = defineModel('open', { type: Boolean, default: false })
    const emit = defineEmits(['created'])
    
    const supabase = useSupabaseClient<any>()
    const toast = useToast()
    const loading = ref(false)

    const newGoal = ref({
        goal_name: '',
        goal_amount: '',
        goal_deadline: ''
    })

    async function saveGoal() {
        if (!newGoal.value.goal_name || !newGoal.value.goal_amount || !newGoal.value.goal_deadline) {
            toast.add({
                title: 'Missing Details',
                description: 'Please fill out all fields to create a goal.',
                color: 'neutral',
                ui: { root: 'bg-red-500 border-2 border-red-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
            return
        }

        loading.value = true

        const { data: { user } } = await supabase.auth.getUser()
        if (!user) {
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
                description: 'Could not find your account log.',
                color: 'neutral',
                ui: { root: 'bg-red-500 border-2 border-red-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
            loading.value = false
            return
        }

        const { error } = await supabase
            .from('GOAL')
            .insert({
                g_name: newGoal.value.goal_name,
                g_amount: parseFloat(newGoal.value.goal_amount),
                g_deadline: newGoal.value.goal_deadline,
                log_id: logData.log_id
            })

        loading.value = false

        if (error) {
            console.error(error)
            toast.add({
                title: 'Database Error',
                description: error.message,
                color: 'neutral',
                ui: { root: 'bg-red-500 border-2 border-red-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
        } else {
            toast.add({
                title: 'Goal Added!',
                description: 'Your new goal is ready to be funded.',
                color: 'neutral',
                ui: { root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
            
            newGoal.value = { goal_name: '', goal_amount: '', goal_deadline: '' }
            
            isOpen.value = false
            emit('created')
        }
    }
</script>