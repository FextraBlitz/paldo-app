<template>
  <div class="flex flex-col min-h-screen bg-white pb-32 text-black">
    <Header />

    <section class="bg-white border-b flex items-center justify-center px-4 py-2 text-sm font-medium text-black">
      <div class="flex items-center gap-4">
          <UButton variant="ghost" icon="i-lucide-chevron-left" size="md" color="error" @click="prevPeriod"/>
          <span class="w-60 text-center">{{ dateRangeDisplay }}</span>
          <UButton variant="ghost" icon="i-lucide-chevron-right" size="md" color="error" @click="nextPeriod"/>
      </div>
      
      <UDropdownMenu :items="filterOptions" :popper="{ placement: 'bottom-end' }">
          <UButton variant="ghost" icon="i-lucide-list-filter" size="md" color="error" />
      </UDropdownMenu>
    </section>

    <section class="flex flex-wrap *:flex-1 *:min-w-[50%] *:ring-1 *:rounded-none *:ring-black *:justify-center *:text-black *:bg-white">
      <UButton> INCOME FLOW </UButton>
      <UButton> EXPPENSE FLOW </UButton>
      <UButton> INCOME OVERVIEW </UButton>
      <UButton> EXPENSE OVERVIEW </UButton>
      <UButton> ACCOUNT ANALYSIS </UButton>
    </section>

    <main class="flex flex-1 flex-col">
      <component class="h-[25vh]" :is="OverviewExpenses" :category_data="category_data"></component>
      <div class="flex flex-col flex-1 min-h-0 overflow-y-scroll">
        <div class="flex border-t-2 last:border-b-2 p-2 items-center" v-for="category in category_names">
          <UIcon :name="category_styles[category]?.icon" :style="{ color: category_styles[category]?.color }" class="text-4xl" />
          <div class="flex flex-col pl-2">
            <div>{{ category }}</div>
          </div>
        </div>
      </div>
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
    <EntryWidget />
  </div>
</template>

<script setup lang="ts">
  import Header from '~/components/header.vue';
  import Footer from '~/components/footer.vue';
  import EntryWidget from '~/components/entry_widget.vue'
  import { ref, computed } from 'vue'
  import { format, startOfWeek, endOfWeek, startOfMonth, endOfMonth, eachDayOfInterval, addDays, subDays, addMonths, subMonths } from 'date-fns'
  import OverviewExpenses from '~/components/charts/overview_expenses.vue';

  // const entries = ref<any[]>([])
  const { data: entries, pending: pendingEntries, refresh: refreshEntries } = await useEntries()
  const { data: categories, pending: pendingCategories, refresh: refreshCategories } = await useCategories()
  // const { data: category_names, pending: pendingCategoryNames } = useCategoryNames()
  const { styles: category_styles, pending: pendingCategoryStyles } = useCategoryStyles()
  const { data: category_data, asArray: category_sums_as_array, pending: pendingCategorySums } = useCategorySums()
  const category_names = computed(() => Object.keys(category_data.value.totals))
  console.log('rawr', category_data.value)

  const currentDate = ref(new Date())
  const viewMode = ref('weekly')
  
  const filterOptions = [
    [
      { label: 'Daily', click: () => { viewMode.value = 'daily' } },
      { label: 'Weekly', click: () => { viewMode.value = 'weekly' } },
      { label: 'Monthly', click: () => { viewMode.value = 'monthly' } }
    ]
  ]

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
    
    return entries.value!.filter(entry => {
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