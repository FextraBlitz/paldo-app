<template>
  <div class="min-h-screen bg-white pb-32">
    <Header />

    <section class="bg-white border-b flex items-center justify-center px-4 py-2 text-sm font-medium text-black">
      <div class="flex items-center gap-4">
          <UButton variant="ghost" icon="i-lucide-chevron-left" size="md" color="error" @click="prevPeriod"/>
          <span class="w-60 text-center">{{ dateRangeDisplay }}</span>
          <UButton variant="ghost" icon="i-lucide-chevron-right" size="md" color="error" @click="nextPeriod"/>
      </div>
      
      <!-- <UDropdownMenu :items="filterOptions" :popper="{ placement: 'bottom-end' }">
          <UButton variant="ghost" icon="i-lucide-list-filter" size="md" color="error" />
      </UDropdownMenu> -->
    </section>

    <section class="flex flex-wrap *:flex-1 *:min-w-[50%] *:ring-1 *:rounded-none *:ring-black *:justify-center *:text-black *:bg-white">
      <UButton @click=""> INCOME FLOW </UButton>
      <UButton> EXPPENSE FLOW </UButton>
      <UButton> INCOME OVERVIEW </UButton>
      <UButton> EXPENSE OVERVIEW </UButton>
      <UButton> ACCOUNT ANALYSIS </UButton>
    </section>

    <main class="p-0 my-1 text-black">
      <component :is="OverviewExpenses" categories=""></component>
    </main>

    <div class="fixed bottom-16 w-full grid grid-cols-3 bg-white border-t border-t-red-900 text-center text-[10px] font-bold py-2 uppercase">
      <div class="border-r border-red-900">
        <div class="text-blue-500">Expenses</div>
        <div class="text-sm text-green-500">₱ {{ periodExpenses.toFixed(2) }}</div>
      </div>
      <div class="border-r border-red-900">
        <div class="text-blue-500">Income</div>
        <div class="text-sm text-red-500">₱ {{ periodIncome.toFixed(2) }}</div>
      </div>
      <div>
        <div class="text-blue-500">Total</div>
        <div class="text-sm" :class="periodBalance < 0 ? 'text-red-500' : 'text-green-500'">
          ₱ {{ periodBalance.toFixed(2) }}
        </div>
      </div>
    </div>

    <Footer />
    <EntryWidget @created="fetchEntries" />
  </div>
</template>

<script setup lang="ts">
  import Header from '~/components/header.vue';
  import Footer from '~/components/footer.vue';
  import EntryWidget from '~/components/entry_widget.vue'
  import { ref, computed, onMounted } from 'vue'
  import { format, startOfWeek, endOfWeek, startOfMonth, endOfMonth, eachDayOfInterval, addDays, subDays, addMonths, subMonths } from 'date-fns'
  import OverviewExpenses from '~/components/charts/overview_expenses.vue';

  const supabase = useSupabaseClient()
  const user = useSupabaseUser()
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

  onMounted(() => {
    fetchEntries()
  })

  
  const fetchCategories = async () => {
    if (!user.value) {
      console.log("no value");
      return []
    }

    console.log(`user value ${user.value.id}`)
    const { data, error } = await supabase
      .from('CATEGORY')
      .select(`
        *,
        LOG!inner (
          user_id
        )
      `)
      .eq('LOG.user_id', user.value.id)

    if (error) {
      console.error('Error fetching categories:', error.message)
      return []
    }
    console.log(`categories ${data}`)
    return data
  }

  watch(user, (newUser) => {
    if (newUser) {
      fetchCategories()
    }
  }, { immediate: true })

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

    if (data) {
      entries.value = data
    }

    console.log('Fetched Data:', data)
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
    
    return entries.value.filter(entry => {
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