<template>
    <div class="min-h-screen bg-slate-50 pb-32">
        <Header />

        <div class="bg-white border-b flex items-center justify-center px-4 py-2 text-sm font-medium text-black">
            <div class="flex items-center gap-4">
                <UButton variant="ghost" icon="i-lucide-chevron-left" size="md" color="error" @click="prevPeriod"/>
                <span class="w-60 text-center">{{ dateRangeDisplay }}</span>
                <UButton variant="ghost" icon="i-lucide-chevron-right" size="md" color="error" @click="nextPeriod"/>
            </div>
            
            <UDropdown :items="filterOptions" :popper="{ placement: 'bottom-end' }">
                <UButton variant="ghost" icon="i-lucide-list-filter" size="md" color="error" />
            </UDropdown>
        </div>

        <main class="p-0">
            <section v-for="(day, index) in daysInRange" :key="index">
                <div class="bg-white px-4 py-2 border-b text-sm text-slate-500 font-medium">
                    {{ format(day, 'MMMM d, yyyy | EEEE') }}
                </div>
                
                <div class="divide-y bg-white">
                    
                    <div 
                        v-for="entry in getEntriesForDay(day)" 
                        :key="entry.entry_id"
                        class="flex items-center px-4 py-3 gap-4 border-b last:border-0"
                    >
                        <div class="w-10 h-10 flex items-center justify-center text-2xl">
                            <UIcon name="i-lucide-receipt" class="w-8 h-8 text-slate-400" />
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-lg leading-tight">
                                {{ entry.CATEGORY?.c_name || 'Uncategorized' }}
                            </div>
                            <div class="text-xs text-slate-500">Cash</div>
                        </div>
                        
                        <div 
                            class="font-medium" 
                            :class="entry.e_type?.toUpperCase() === 'I' ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ entry.e_type?.toUpperCase() === 'E' ? '- ₱' : '+ ₱' }} {{ Number(entry.e_amount).toFixed(2) }}
                        </div>
                    </div>

                    <div v-if="getEntriesForDay(day).length === 0" class="px-4 py-3 text-xs text-slate-400 italic text-center">
                        No transactions
                    </div>

                </div>
            </section>
        </main>

        <div class="fixed bottom-32 right-4 z-20 text-center font-bold">
            <UButton
                icon="i-lucide-plus"
                size="xl"
                :ui="{ base: 'items-center justify-center p-0' }"
                class="rounded-full w-14 h-14 border-4 border-red-900 bg-red text-white"
            />
        </div>

        <div class="fixed bottom-16 w-full grid grid-cols-3 bg-white border-t border-black text-center text-[10px] font-bold py-2 uppercase">
            <div class="border-r border-black">
                <div class="text-blue-500">Expenses</div>
                <div class="text-sm">₱ 500.00</div>
            </div>
            <div class="border-r border-black">
                <div class="text-blue-500">Income</div>
                <div class="text-sm">₱ 5,000.00</div>
            </div>
            <div>
                <div class="text-blue-500">Total</div>
                <div class="text-sm">₱ 4,500.00</div>
            </div>
        </div>

        <Footer />
    </div>
</template>

<script setup lang="ts">
    import Header from '~/components/header.vue';
    import Footer from '~/components/footer.vue';
    import { ref, computed, onMounted } from 'vue' // Added onMounted
    import { format, startOfWeek, endOfWeek, startOfMonth, endOfMonth, eachDayOfInterval, addDays, subDays, addMonths, subMonths } from 'date-fns'

    // Initialize Supabase client and state array for entries
    const supabase = useSupabaseClient()
    const entries = ref<any[]>([])

    const currentDate = ref(new Date())
    const viewMode = ref('weekly')

    const filterOptions = [
        [
            { label: 'Daily', click: () => { viewMode.value = 'daily' } },
            { label: 'Weekly', click: () => { viewMode.value = 'weekly' } },
            { label: 'Monthly', click: () => { viewMode.value = 'monthly' } }
        ]
    ]

    // Fetch entries when the page loads
    onMounted(() => {
        fetchEntries()
    })

    async function fetchEntries() {
        const { data, error } = await supabase
            .from('ENTRY')
            .select(`
                entry_id,
                e_type,
                e_amount,
                e_date,
                CATEGORY ( c_name )
            `)
            .order('e_date', { ascending: false })

        if (error) {
            console.error('Error fetching entries:', error.message)
            return
        }

        // Save the fetched data to our reactive variable
        if (data) {
            entries.value = data
        }

        console.log('Fetched Data:', data)
    }

    // Helper function to filter the big entries list down to just the ones for a specific day
    function getEntriesForDay(day: Date) {
        // Formats the javascript Date to 'YYYY-MM-DD'
        const dateString = format(day, 'yyyy-MM-dd')
        
        return entries.value.filter(entry => {
            // Checks if '2026-01-05T05:00:45+00:00' starts with '2026-01-05'
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
        } else if (viewMode.value === 'monthly') {
            return format(currentDate.value, 'MMMM yyyy')
        } else {
            const start = startOfWeek(currentDate.value, { weekStartsOn: 0 })
            const end = endOfWeek(currentDate.value, { weekStartsOn: 0 })
            const monthStart = format(start, 'MMMM d')
            const monthEnd = format(end, 'MMMM d, yyyy')
            return `${monthStart} – ${monthEnd}`
        }
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