<template>
    <div class="min-h-screen bg-white pb-32">
        <div class="bg-white border-b flex items-center justify-center px-4 py-2 text-sm font-medium text-black">
            <div class="flex items-center gap-4">
                <UButton variant="ghost" icon="i-lucide-chevron-left" size="md" color="error" @click="prevPeriod"/>
                <span class="w-60 text-center">{{ dateRangeDisplay }}</span>
                <UButton variant="ghost" icon="i-lucide-chevron-right" size="md" color="error" @click="nextPeriod"/>
            </div>
            
            <UDropdownMenu
                :items="filterOptions"
                :popper="{ placement: 'bottom-end' }"
                :ui="{ 
                    content: 'bg-white ring-0 border border-slate-300 shadow-lg rounded-md',
                    item: 'text-red-500 hover:text-red-500'
                }"
            >
                <UButton variant="ghost" icon="i-lucide-list-filter" size="md" color="error" />
            </UDropdownMenu>
        </div>

        <main class="p-0 my-1">
            <section v-for="(day, index) in daysInRange" :key="index">
                <div class="bg-white mx-4 px-4 py-2 border-b text-sm text-slate-700 font-medium">
                    {{ format(day, 'MMMM d, yyyy | EEEE') }}
                </div>
                
                <div class="divide-y bg-white">
                    <div 
                        v-for="entry in getEntriesForDay(day)" 
                        :key="entry.entry_id"
                        class="flex items-center px-4 py-3 gap-4 border-b last:border-0"
                    >
                        <div 
                            class="flex items-center justify-center w-8 h-8 rounded-full"
                            :style="{ backgroundColor: category_styles.value?.color || 'slategray' }"
                        >
                            <UIcon :name="category_styles.value?.icon || 'i-game-icons-two-coins'" class="w-7.5 h-7.5 text-white" />
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-slate-700 leading-tight">
                                {{ entry.CATEGORY?.c_name || 'Uncategorized' }}
                            </div>
                            <div class="text-xs text-slate-700">Cash</div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div 
                                class="font-medium" 
                                :class="entry.e_type?.toUpperCase() === 'I' ? 'text-green-500' : 'text-red-500'"
                            >
                                {{ entry.e_type?.toUpperCase() === 'E' ? '- ₱' : '+ ₱' }} {{ Number(entry.e_amount).toFixed(2) }}
                            </div>
                            
                            <UDropdownMenu 
                                :items="getDropdownItems(entry)"
                                :ui="{
                                    content: 'bg-white ring-0 border border-slate-300 shadow-lg rounded-md'
                                }"
                            >
                                <UButton variant="ghost" color="error" icon="i-lucide-more-horizontal" class="text-slate-400" />
                            </UDropdownMenu>
                        </div>
                    </div>

                    <div v-if="getEntriesForDay(day).length === 0" class="px-4 py-3 text-xs text-slate-400 italic text-center">
                        No transactions
                    </div>
                </div>
            </section>
        </main>

        <div class="fixed bottom-16 inset-x-0 max-w-[inherit] w-full grid grid-cols-3 bg-white border-t border-t-slate-700 text-center text-[10px] font-bold py-2 uppercase">
            <div class="border-r border-r-slate-700">
                <div class="text-blue-500">Expenses</div>
                <div class="text-sm text-red-500">₱ {{ periodExpenses.toFixed(2) }}</div>
            </div>
            <div class="border-r border-r-slate-700">
                <div class="text-blue-500">Income</div>
                <div class="text-sm text-green-500">₱ {{ periodIncome.toFixed(2) }}</div>
            </div>
            <div>
                <div class="text-blue-500">Total</div>
                <div class="text-sm" :class="periodBalance < 0 ? 'text-red-500' : 'text-green-500'">
                    ₱ {{ periodBalance.toFixed(2) }}
                </div>
            </div>
        </div>

        <AddEntry @created="refreshEntries" />
        <EditEntry 
            v-model:open="isEditModalOpen" 
            :entry="selectedEntry" 
            @updated="refreshEntries" 
        />
        <DeleteEntry 
            v-model:open="isDeleteModalOpen" 
            :entry="selectedEntry" 
            @deleted="refreshEntries" 
        />
    </div>
</template>

<script setup lang="ts">
    import Header from '~/components/header.vue';
    import Footer from '~/components/footer.vue';
    import AddEntry from '~/components/add_entry.vue'
    import EditEntry from '~/components/edit_entry.vue';
    import DeleteEntry from '~/components/delete_entry.vue';
    import { ref, computed, onMounted } from 'vue'
    import { format, startOfWeek, endOfWeek, startOfMonth, endOfMonth, eachDayOfInterval, addDays, subDays, addMonths, subMonths } from 'date-fns'

    const supabase = useSupabaseClient()
    // const entries = ref<any[]>([])
    const currentDate = ref(new Date())
    const viewMode = ref('weekly')
    const searchQuery = ref('')
    const isEditModalOpen = ref(false)
    const isDeleteModalOpen = ref(false)
    const selectedEntry = ref<any>(null)

    const { data: entries, pending: pendingEntries, refresh: refreshEntries } = useEntries()
    const { styles: category_styles, refresh: refreshStyles, pending: pendingCategoryStyles } = useCategoryStyles()
    const loading_states = useLoadingStates()
    watch(pendingEntries, () => {loading_states.states.value['entries']=pendingEntries.value})

    const filterOptions = [
        [
            { label: 'Daily', onSelect: () => { viewMode.value = 'daily' } },
            { label: 'Weekly', onSelect: () => { viewMode.value = 'weekly' } },
            { label: 'Monthly', onSelect: () => { viewMode.value = 'monthly' } }
        ]
    ]

    const getDropdownItems = (entry: any) => [
        [{
            label: 'Edit',
            icon: 'i-lucide-pencil',
            color: 'info',
            onSelect: () => {
                selectedEntry.value = entry
                isEditModalOpen.value = true
            }
        },
        {
            label: 'Delete',
            icon: 'i-lucide-trash-2',
            color: 'error',
            onSelect: () => {
                selectedEntry.value = entry
                isDeleteModalOpen.value = true
            }
        }]
    ]

    const filteredEntries = computed(() => {
        if (!searchQuery.value) return entries.value ?? []
        if (!entries.value) return []

        const query = searchQuery.value.toLowerCase()
        
        return entries.value.filter(entry => {
            const categoryName = entry.CATEGORY?.c_name || 'Uncategorized'
            return categoryName.toLowerCase().includes(query)
        })
    })

    function getEntriesForDay(day: Date) {
        const dateString = format(day, 'yyyy-MM-dd')
        
        return filteredEntries.value.filter(entry => {
            if (!entry.e_date) return false;
            return entry.e_date.startsWith(dateString)
        })
    }

    const daysInRange = computed(() => {
        if (viewMode.value === 'daily') {
            return [currentDate.value]
        } 
        else if (viewMode.value === 'weekly') {
            const start = startOfWeek(currentDate.value, { weekStartsOn: 0 })
            const end = endOfWeek(currentDate.value, { weekStartsOn: 0 })
            return eachDayOfInterval({ start, end }).reverse()
        } 
        else if (viewMode.value === 'monthly') {
            const start = startOfMonth(currentDate.value)
            const end = endOfMonth(currentDate.value)
            return eachDayOfInterval({ start, end }).reverse()
        }
        return []
    })

    const dateRangeDisplay = computed(() => {
        if (viewMode.value === 'daily') {
            return format(currentDate.value, 'MMMM d, yyyy')
        }
        else if (viewMode.value === 'monthly') {
            return format(currentDate.value, 'MMMM yyyy')
        }
        else {
            const start = startOfWeek(currentDate.value, { weekStartsOn: 0 })
            const end = endOfWeek(currentDate.value, { weekStartsOn: 0 })
            const monthStart = format(start, 'MMMM d')
            const monthEnd = format(end, 'MMMM d, yyyy')
            return `${monthStart} – ${monthEnd}`
        }
    })

    const visibleEntries = computed(() => {
        const visibleDates = new Set(daysInRange.value.map(day => format(day, 'yyyy-MM-dd')))
        
        return filteredEntries.value.filter(entry => {
            if (!entry.e_date) return false
            const entryDate = entry.e_date.substring(0, 10) 
            return visibleDates.has(entryDate)
        })
    })

    const periodIncome = computed(() => {
        return visibleEntries.value
            .filter(e => e.e_type?.toUpperCase() === 'I')
            .reduce((sum, e) => sum + Number(e.e_amount), 0)
    })
    const periodExpenses = computed(() => {
        return visibleEntries.value
            .filter(e => e.e_type?.toUpperCase() === 'E')
            .reduce((sum, e) => sum + Number(e.e_amount), 0)
    })
    const periodBalance = computed(() => {
        return periodIncome.value - periodExpenses.value
    })

    function nextPeriod() {
        if (viewMode.value === 'daily') currentDate.value = addDays(currentDate.value, 1)
        else if (viewMode.value === 'monthly') currentDate.value = addMonths(currentDate.value, 1)
        else currentDate.value = addDays(currentDate.value, 7)
    }
    function prevPeriod() {
        if (viewMode.value === 'daily') currentDate.value = subDays(currentDate.value, 1)
        else if (viewMode.value === 'monthly') currentDate.value = subMonths(currentDate.value, 1)
        else currentDate.value = subDays(currentDate.value, 7)
    }
</script>