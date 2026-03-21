<template>
  <div class="flex flex-col min-h-screen bg-white pb-32 text-black">
    <Header />

    <section class="bg-white border-b flex items-center justify-center px-4 py-2 text-sm font-medium text-black">
      <div class="flex items-center gap-4">
          <UButton variant="ghost" icon="i-lucide-chevron-left" size="md" color="error" @click="prevPeriod"/>
          <span class="w-60 text-center">{{ dateRangeDisplay }}</span>
          <UButton variant="ghost" icon="i-lucide-chevron-right" size="md" color="error" @click="nextPeriod"/>
      </div>
      
      <USelect v-model="viewMode" trailing-icon="none" :items="filterOptions" :popper="{ placement: 'bottom-end' }" :ui="{trailing:'hidden', base:'pe-0.5 px-0.5 py-0.5 bg-transparent', content: 'w-fit bg-white', item: 'text-red-500'}">
          <UButton variant="ghost" icon="i-lucide-list-filter" size="md" color="error" />
      </USelect>
    </section>

    <section class="flex flex-wrap *:flex-1 *:min-w-[50%] *:ring-1 *:rounded-none *:ring-black *:justify-center *:text-white">
      <UButton @click="switchChart('flow-income')" :active="activeChart === 'flow-income'" class="bg-blue-500 active:bg-blue-700 hover:bg-blue-700" active-class="font-bold !bg-blue-700"> INCOME FLOW </UButton>
      <UButton @click="switchChart('flow-expenses')" :active="activeChart === 'flow-expenses'" class="bg-blue-500 active:bg-blue-700 hover:bg-blue-700" active-class="font-bold !bg-blue-700"> EXPENSE FLOW </UButton>
      <UButton @click="switchChart('overview-income')" :active="activeChart === 'overview-income'" class="bg-blue-500 active:bg-blue-700 hover:bg-blue-700" active-class="font-bold !bg-blue-700"> INCOME OVERVIEW </UButton>
      <UButton @click="switchChart('overview-expenses')" :active="activeChart === 'overview-expenses'" class="bg-blue-500 active:bg-blue-700 hover:bg-blue-700" active-class="font-bold !bg-blue-700"> EXPENSE OVERVIEW </UButton>
      <UButton @click="switchChart('analysis-account')" :active="activeChart === 'analysis-account'" class="bg-blue-500 active:bg-blue-700 hover:bg-blue-700" active-class="font-bold !bg-blue-700"> ACCOUNT ANALYSIS </UButton>
    </section>

    <main class="flex-1">
      <component :is="ChartComponents[activeChart]" :category_data="category_data" :entry_data="formatted_entries" ></component>
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
    <AddEntry @created="refreshValues()" />
  </div>
</template>

<script setup lang="ts">
  import Header from '~/components/header.vue';
  import Footer from '~/components/footer.vue';
  import AddEntry from '~/components/add_entry.vue'
  import { ref, computed } from 'vue'
  import { format, startOfWeek, endOfWeek, startOfMonth, endOfMonth, eachDayOfInterval, addDays, subDays, addMonths, subMonths } from 'date-fns'
  import OverviewExpenses from '~/components/charts/overview_expenses.vue';
  import OverviewIncome from '~/components/charts/overview_income.vue';
  import FlowIncome from '~/components/charts/flow_income.vue';
  import FlowExpenses from '~/components/charts/flow_expenses.vue';
  import AnalysisAccount from '~/components/charts/analysis_account.vue'; 

  // const entries = ref<any[]>([])
  
  const currentDate = ref(new Date())
  const viewMode = ref('weekly')
  const daysInRange = computed(() => {
    if (viewMode.value === 'daily') {
      const next_day = new Date(currentDate.value)
      next_day.setDate(next_day.getDate() + 1)
      return [currentDate.value, next_day]
    }
    if (viewMode.value === 'weekly') {
      const start = startOfWeek(currentDate.value, { weekStartsOn: 0 })
      const end   = endOfWeek(currentDate.value, { weekStartsOn: 0 })
      end.setDate(end.getDate() + 1)
      return eachDayOfInterval({ start, end })
    }
    if (viewMode.value === 'monthly') {
      const start = startOfMonth(currentDate.value)
      const end   = endOfMonth(currentDate.value)
      return eachDayOfInterval({ start, end })
    }
    return []
  })

  const startDate = computed(() => {
    const date = daysInRange.value?.[0]
    console.log('stard', date);
    return date ? format(date, 'yyyy-MM-dd') : null
  })

  const endDate = computed(() => {
    const date = daysInRange.value?.[daysInRange.value.length - 1]
    console.log('end', date);
    return date ? format(date, 'yyyy-MM-dd') : null
  })
  
  const { data: categories, pending: pendingCategories, refresh: refreshCategories } = await useCategories()
  const { data: entries, pending: pendingEntries, refresh: refreshEntries } = useEntries(startDate, endDate)
  const { data: category_data } = useCategorySums(startDate, endDate)
  const { data: formatted_entries } = useFormattedEntries(startDate, endDate)
  
  console.log('rawr', category_data.value)

  type viewModes = 'overview-expenses' | 'overview-income' | 'flow-expenses' | 'flow-income' | 'analysis-account'

  const ChartComponents: Record<viewModes, Component> = {
    'overview-expenses': OverviewExpenses,
    'overview-income': OverviewIncome,
    'flow-expenses': FlowExpenses,
    'flow-income': FlowIncome,
    'analysis-account': AnalysisAccount,
  }
  const activeChart = ref<viewModes>('analysis-account')

  const switchChart = (newChart: viewModes) => {
    activeChart.value = newChart
    refreshValues()
  }
  const refreshValues = () => {
    refreshEntries()
    refreshCategories()
    // console.log('entries', entries.value)
    // console.log('categories', category_data.value)
  }
  
  const filterOptions = [
    {label: 'Daily', value: 'daily'},
    {label: 'Weekly', value: 'weekly'},
    {label: 'Monthly', value: 'monthly'},
  ]


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
    // Use a fallback to prevent .map of undefined error
    const days = daysInRange.value || []
    const visibleDates = new Set(days.map(day => format(day, 'yyyy-MM-dd')))
    
    // CRITICAL: If entries are null (fetching), return empty array so stats remain 0 
    // rather than throwing the 'filter of undefined' error
    if (!entries.value) return []

    return entries.value.filter(entry => {
      if (!entry.e_date) return false
      const entryDate = entry.e_date.substring(0, 10) 
      return visibleDates.has(entryDate)
    })
  })

  const periodIncome = computed(() => {
    return visibleEntries.value.filter(e => {console.log('aawaw'); return e.e_type?.toUpperCase() === 'I'}).reduce((sum, e) => {console.log(e, 'bla') ;return sum + Number(e.e_amount)}, 0)
  })
  const periodExpenses = computed(() => {
    return visibleEntries.value.filter(e => e.e_type?.toUpperCase() === 'E').reduce((sum, e) => sum + Number(e.e_amount), 0)
  })
  const periodBalance = computed(() => {
    return periodIncome.value - periodExpenses.value
  })

  function nextPeriod() {
    if (viewMode.value === 'daily') currentDate.value = addDays(currentDate.value, 1)
    else if (viewMode.value === 'monthly') currentDate.value = addMonths(currentDate.value, 1)
    else currentDate.value = addDays(currentDate.value, 7)
    refreshEntries()
  }
  function prevPeriod() {
    if (viewMode.value === 'daily') currentDate.value = subDays(currentDate.value, 1)
    else if (viewMode.value === 'monthly') currentDate.value = subMonths(currentDate.value, 1)
    else currentDate.value = subDays(currentDate.value, 7)
    refreshEntries()
    refreshValues()
  }
</script>