<template>
    <div class="min-h-screen bg-white pb-40">
        <Header />

        <div class="bg-white border-b-2 border-slate-500 py-2 text-center text-sm font-bold uppercase text-slate-700">
            <h2 class="text-sm font-bold text-gray-700">CURRENT GOALS</h2>
        </div>

        <div class="p-4 grid grid-cols-3 gap-2">
            <div class="bg-white border-2 border-blue-300 rounded-md p-2 flex flex-col items-center justify-center text-center">
                <span class="text-[10px] font-bold text-red-500">TOTAL GOALS</span>
                <span class="text-2xl font-bold text-blue-500 leading-none my-1">{{ activeGoalsCount }}</span>
                <span class="text-[10px] font-bold text-red-500">ACTIVE</span>
            </div>

            <div class="bg-white border-2 border-blue-300 rounded-md p-2 flex flex-col justify-center text-center">
                <span class="text-[10px] font-bold text-red-500">TOTAL SAVED</span>
                <span class="text-sm font-bold" :class="totalBalance < 0 ? 'text-red-500' : 'text-green-500'">
                    ₱ {{ formatCurrency(totalBalance) }}
                </span>
                <span class="text-[9px] text-slate-500">/ ₱ {{ formatCurrency(overallTarget) }}</span>
                <div class="w-full h-1.5 bg-gray-100 mt-1 border border-blue-700 rounded-xs">
                    <div class="h-full bg-blue-500" :style="{ width: `${overallPercentage}%` }"></div>
                </div>
            </div>

            <div class="bg-white border-2 border-blue-300 rounded-md p-2 flex flex-col justify-center text-center">
                <span class="text-[10px] font-bold text-red-500">COMPLETION</span>
                <span class="text-xl font-bold text-blue-500 my-1">{{ overallPercentage.toFixed(0) }}%</span>
                <div class="w-full h-1.5 bg-gray-100 border border-blue-700 rounded-xs">
                    <div class="h-full bg-blue-500" :style="{ width: `${overallPercentage}%` }"></div>
                </div>
            </div>
        </div>

        <div class="px-4 space-y-4">
            <div v-if="loading" class="text-center py-10 text-slate-500 font-medium">Loading goals...</div>
            
            <div v-else-if="processedGoals.length === 0" class="text-center py-10 text-slate-500 font-medium">
                No active goals. Add one to get started!
            </div>

            <div 
                v-for="goal in processedGoals" 
                :key="goal.goal_id"
                class="flex items-stretch h-24 border-t border-slate-400"
            >
                <button 
                    @click="promptCompleteGoal(goal.goal_id)"
                    class="w-20 flex items-center justify-center shrink-0 cursor-pointer hover:bg-green-50 transition-colors"
                    title="Mark as completed"
                >
                    <UIcon name="i-lucide-circle-check" class="w-12 h-12 text-green-500 hover:text-green-300 transition-transform duration-200" />
                </button>

                <div class="flex-1 p-2 flex flex-col justify-between overflow-hidden">
                    <div>
                        <h3 class="font-bold text-sm text-slate-800 truncate">{{ goal.g_name || 'My Goal' }}</h3>
                        <p class="text-[10px] font-bold text-slate-600">Deadline: <span class="text-red-500">{{ formatDate(goal.g_deadline) }}</span></p>
                    </div>

                    <div class="w-full h-3 bg-gray-100 border border-blue-700 my-1 rounded-sm">
                        <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: `${goal.percentage}%` }"></div>
                    </div>

                    <p class="text-[10px] text-blue-500">₱ {{ calculateMonthly(goal.g_amount, goal.g_deadline) }}/month needed</p>
                </div>

                <div class="w-24 flex flex-col items-center justify-center p-1 shrink-0 text-center">
                    <span class="text-[16px] text-slate-800">₱ {{ formatCurrency(goal.allocated_amount) }}</span>
                    <span class="text-[12px] text-slate-500 mb-1">/ ₱ {{ formatCurrency(goal.g_amount) }}</span>
                    <span class="text-md font-bold text-white bg-blue-500 p-1 w-full rounded-xl">{{ goal.percentage.toFixed(2) }}%</span>
                </div>
            </div>
        </div>

        <div class="px-4 mt-6">
            <UButton @click="isAddGoalOpen = true" block color="info" variant="outline" size="lg" class="font-bold border-black text-blue-500 hover:bg-gray-50 uppercase">
                Add New Goal
            </UButton>
        </div>
        <AddGoal v-model:open="isAddGoalOpen" @created="fetchGoals" />

        <div class="fixed bottom-16 w-full grid grid-cols-3 bg-white border-t border-slate-700 text-center text-[10px] font-bold py-2 uppercase">
            <div class="border-r border-r-slate-700">
                <div class="text-blue-500">Expenses</div>
                <div class="text-sm font-bold text-red-500">₱ {{ formatCurrency(totalExpense) }}</div> 
            </div>
            <div class="border-r border-r-slate-700">
                <div class="text-blue-500">Income</div>
                <div class="text-sm font-bold text-green-500">₱ {{ formatCurrency(totalIncome) }}</div>
            </div>
            <div class="border-r border-r-slate-700">
                <div class="text-blue-500">Total</div>
                <div class="text-sm font-bold" :class="totalBalance < 0 ? 'text-red-500' : 'text-green-500'">
                    ₱ {{ formatCurrency(totalBalance) }}
                </div>
            </div>
        </div>

        <UModal v-model:open="isConfirmCompleteOpen">
            <template #content>
                <UCard class="divide-y divide-gray-100 bg-slate-200">
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold leading-6 text-green-600 uppercase">Complete Goal</h3>
                            <UButton color="success" variant="ghost" icon="i-lucide-x" class="-my-1" @click="isConfirmCompleteOpen = false" />
                        </div>
                    </template>

                    <div class="py-4 space-y-4 text-center">
                        <p class="text-slate-700 font-medium">
                            Are you sure you want to mark this goal as completed?
                        </p>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <UButton block color="neutral" variant="solid" size="lg" class="flex-1 font-bold uppercase" @click="isConfirmCompleteOpen = false">
                            Cancel
                        </UButton>
                        <UButton block color="success" size="lg" :loading="loading" class="flex-1 font-bold uppercase text-white" @click="executeCompleteGoal">
                            Confirm
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <Footer />
    </div>
</template>

<script setup lang="ts">
    import { ref, onMounted, computed } from 'vue'
    import { format, differenceInMonths, differenceInDays } from 'date-fns'
    import Header from '~/components/header.vue'
    import Footer from '~/components/footer.vue'
    import AddGoal from '~/components/add_goal.vue'

    const supabase = useSupabaseClient<any>()
    const loading = ref(true)
    const toast = useToast()
    const isAddGoalOpen = ref(false)

    const totalBalance = ref(0)
    const totalExpense = ref(0)
    const totalIncome = ref(0)
    const rawGoals = ref<any[]>([])
    const isConfirmCompleteOpen = ref(false)
    const goalToComplete = ref<string | null>(null)

    const processedGoals = computed(() => {
        let remainingBalance = totalBalance.value
        
        return rawGoals.value.map(goal => {
            const amountNeeded = parseFloat(goal.g_amount)
            let allocatedAmount = 0
            
            if (remainingBalance >= amountNeeded) {
                allocatedAmount = amountNeeded
                remainingBalance -= amountNeeded
            }
            else if (remainingBalance > 0) {
                allocatedAmount = remainingBalance
                remainingBalance = 0
            }
            else {
                allocatedAmount = 0
            }

            const percentage = amountNeeded > 0 ? (allocatedAmount / amountNeeded) * 100 : 0

            return {
                ...goal,
                allocated_amount: allocatedAmount,
                percentage: Math.min(percentage, 100)
            }
        })
    })

    const activeGoalsCount = computed(() => rawGoals.value.length)
    
    const overallTarget = computed(() => {
        return rawGoals.value.reduce((sum, goal) => sum + parseFloat(goal.g_amount), 0)
    })

    const overallPercentage = computed(() => {
        if (overallTarget.value === 0 || totalBalance.value <= 0) return 0
        return Math.min((totalBalance.value / overallTarget.value) * 100, 100)
    })

    onMounted(async () => {
        fetchGoals()
    })

    async function fetchGoals() {
        loading.value = true
        const { data: { user } } = await supabase.auth.getUser()
        
        if (user) {
            const { data: logData } = await supabase
                .from('LOG')
                .select('log_id, total_balance, total_expense, total_income')
                .eq('user_id', user.id)
                .single()

            if (logData) {
                totalBalance.value = parseFloat(logData.total_balance) || 0
                totalExpense.value = parseFloat(logData.total_expense) || 0
                totalIncome.value = parseFloat(logData.total_income) || 0

                const { data: goalsData } = await supabase
                    .from('GOAL')
                    .select('*')    
                    .eq('log_id', logData.log_id)
                    .is('is_completed', false)
                    .order('g_deadline', { ascending: true })

                if (goalsData) {
                    rawGoals.value = goalsData
                }
            }
        }
        loading.value = false
    }

    function promptCompleteGoal(goalId: string) {
        goalToComplete.value = goalId
        isConfirmCompleteOpen.value = true
    }

    async function executeCompleteGoal() {
        if (!goalToComplete.value) return

        loading.value = true

        const { error } = await supabase
            .from('GOAL')
            .update({ is_completed: true })
            .eq('goal_id', goalToComplete.value)

        if (error) {
            console.error(error)
            toast.add({
                title: 'Error',
                description: 'Could not mark goal as completed.',
                color: 'neutral',
                ui: { root: 'bg-red-500 border-2 border-red-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
            loading.value = false
        } else {
            toast.add({
                title: 'Congratulations!',
                description: 'Goal marked as completed.',
                color: 'neutral',
                ui: { root: 'bg-green-500 border-2 border-green-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
            
            isConfirmCompleteOpen.value = false
            goalToComplete.value = null
            await fetchGoals()
        }
    }

    function formatCurrency(value: number) {
        return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    }

    function formatDate(dateString: string) {
        if (!dateString) return 'No Date'
        return format(new Date(dateString), 'MMMM dd, yyyy')
    }

    function calculateMonthly(targetAmount: number, deadline: string) {
        if (!deadline || targetAmount <= 0) return '0'
        
        const targetDate = new Date(deadline)
        const today = new Date()
        
        const daysLeft = differenceInDays(targetDate, today)
        if (daysLeft < 0) return 'Overdue' 
        
        const monthsLeft = differenceInMonths(targetDate, today)
        
        if (monthsLeft <= 0) return formatCurrency(targetAmount)
        
        return formatCurrency(targetAmount / monthsLeft)
    }
</script>