<template>
  <div v-if="Object.keys(props.entry_data).length === 0" class="flex flex-col h-64 justify-center items-center font-bold text-4xl">
    No data to show.
  </div>
  <div class="flex flex-1 flex-col">
    <div class="flex items-center justify-center aspect-square p-4 h-[25vh]">
      <Line v-if="activeChart == 'time'" :data="line_data" :options="options"> </Line>
      <Bar v-if="activeChart == 'total'" :data="bar_data" :options="options"> </Bar>
      
    </div>
    <section class="flex flex-wrap px-2 py-1 gap-2 *:flex-1 *:ring-1 *:rounded-xl *:justify-center *:text-white">
      <UButton @click="switchChart('total')" :active="activeChart === 'total'" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> TOTAL </UButton>
      <UButton @click="switchChart('time')" :active="activeChart === 'time'" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> TIME </UButton>
    </section>
    <section class="flex *:flex-1 px-2 py-1 gap-2 *:ring-1 *:rounded-xl *:justify-center *:text-white">
      <UButton @click="switchScale('expenses')" :active="activeScales.has('expenses')" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> EXPENSES </UButton>
      <UButton @click="switchScale('totals')" :active="activeScales.has('totals')" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> TOTAL </UButton>
      <UButton @click="switchScale('income')" :active="activeScales.has('income')" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> INCOME </UButton>
    </section>
    <section class="flex justify-center gap-4 mx-2 py-3 border-b border-slate-300 text-[10px] font-bold uppercase">
      <div 
        v-for="scale in ['income', 'totals', 'expenses']" 
        :key="scale"
        class="flex items-center gap-1 transition-opacity duration-200"
        :class="activeScales.has(scale as scaleMode) ? 'opacity-100' : 'opacity-20'"
      >
        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: colorMap[scale as scaleMode] }"></span>
        <span>{{ scale }}</span>
      </div>
    </section>
    <!-- NEW: Report button section -->
    <div class="flex items-center justify-center gap-3 py-4 flex-wrap">
      <USelect
        v-model="reportPeriod"
        :items="periodOptions"
        placeholder="Select period"
        :ui="{
          trailing:'',
          placeholder: 'text-slate-600',
          base:'rounded-md bg-transparent text-black hover:bg-slate-100',
          content: 'rounded-sm bg-slate-100 ring-slate-400',
          item: 'text-black checked:bg-red-200'
          }"
      />
      <UButton
        style="--ui-color-primary-400: oklch(62.3% 0.214 259.815)"
        class="ring-1 rounded-xl ring-blue-700 justify-center text-white bg-blue-500 active:bg-blue-500! hover:bg-blue-700 uppercase"
        active-class="font-bold !bg-blue-700"
        :loading="isSendingReport"
        :disabled="isSendingReport || !hasData"
        @click="sendReport"
      >
        Send Report
      </UButton>
    </div>
  </div>
</template>

<script setup lang="ts">

import { Chart as ChartJS, Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, LineController, BarElement, BarController } from 'chart.js'
import { Line, Bar } from 'vue-chartjs'
ChartJS.register(Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, LineController, BarElement, BarController )
const user = useSupabaseUser() // assuming you already have this somewhere

interface Props {
  category_data: {
    totals: Record<string, number>;
    expenses: Record<string, number>;
    income: Record<string, number>;
  };
  entry_data: {
    totals: FormattedEntry[];
    expenses: FormattedEntry[];
    income: FormattedEntry[];
  };
}
const props = defineProps<Props>()

type chartMode = 'total' | 'time'
type scaleMode = 'totals' | 'income' | 'expenses'

// 1. Centralized Color Map
const colorMap: Record<scaleMode, string> = {
  totals: 'rgb(75, 192, 192)',
  expenses: 'rgb(55, 66, 77)',
  income: 'rgb(123, 122, 77)'
}

const activeChart = ref<chartMode>('time')
const activeScales = ref<Set<scaleMode>>(new Set(['totals', 'income', 'expenses'])) // Default all on

const switchChart = (newChart: chartMode) => {
  activeChart.value = newChart
}
const switchScale = (newScale: scaleMode) => {
  const next = new Set(activeScales.value)
  if (next.has(newScale)) next.delete(newScale)
  else next.add(newScale)
  activeScales.value = next // Re-assign to trigger reactivity
}

const sum_expenses = computed(() => {
  const values = Object.values(sumOfDailyEntries(props.entry_data.expenses || []))
  return values.length ? values.reduce((sum, curr) => sum + curr) : 0
})

const sum_income = computed(() => {
  const values = Object.values(sumOfDailyEntries(props.entry_data.income || []))
  return values.length ? values.reduce((sum, curr) => sum + curr) : 0
})
const sum_total = computed(() => props.entry_data.totals?.at(-1)?.amount ?? 0)

const labels = props.entry_data.totals.map(entry => entry.id);

const bar_data_values = computed(() => {
  const result: Record<string, number> = {}

  if (activeScales.value.has('expenses')) {
    result['Expenses'] = sum_expenses.value
  }
  if (activeScales.value.has('totals')) {
    result['Total'] = sum_total.value ?? 0
  }
  if (activeScales.value.has('income')) {
    result['Income'] = sum_income.value
  }
  console.log(result)
  return result
})

const lastOfDailyEntries = (entries: FormattedEntry[]) => {
  const result: Record<number, number> = {}
  const daily_groups = groupEntriesByDay(entries)
  for (const [day, dayEntries] of Object.entries(daily_groups)) {
    // For totals/balance, we want the state at the end of the day
    result[parseInt(day)] = dayEntries.at(-1)?.amount ?? 0
  }
  return result
}

// --- LINE CHART DATA ---

const allTimestamps = computed(() => {
  const timestamps = new Set<number>()

  // Collect every day that has ANY transaction (expenses, income, or totals)
  ;['expenses' as const, 'income' as const, 'totals' as const].forEach(scale => {
    const entries = props.entry_data[scale] || []
    entries.forEach(e => {
      if (e?.date) timestamps.add(msToDay(e.date.getTime()))
    })
  })

  return Array.from(timestamps).sort((a, b) => a - b)
})

const ensureEnoughPoints = (dailyMap: Record<number, number>, allTimestamps: number[]) => {
  const values = allTimestamps.map(t => dailyMap[t] ?? null)
  const nonNullValues = values.filter(v => v !== null)

  // If 0 or 1 real data point → duplicate to force horizontal line
  if (nonNullValues.length <= 1) {
    // Find the single value (or default to 0)
    const singleValue = nonNullValues[0] ?? 0

    // Create two points with same value but different x (first + last timestamp)
    if (allTimestamps.length >= 2) {
      const newData = allTimestamps.map((t, i) => {
        // Put the value at start and end, nulls in between if any
        if (i === 0 || i === allTimestamps.length - 1) return singleValue
        return null
      })
      return newData
    }
    // If only one timestamp total → can't duplicate → just return original
  }

  return allTimestamps.map(t => dailyMap[t] ?? null)
}

// Pre-compute sorted daily maps using your exact functions
const dailyExpenses = computed(() => sortDailyEntries(sumOfDailyEntries(props.entry_data.expenses || [])))
const dailyIncome    = computed(() => sortDailyEntries(sumOfDailyEntries(props.entry_data.income || [])))
const dailyTotals    = computed(() => sortDailyEntries(lastOfDailyEntries(props.entry_data.totals || [])))
// --- LINE CHART DATA (TIME) ---
// 2. Reactive Line Data
const line_labels = computed(() => Object.entries(sumOfDailyEntries(props.entry_data['totals'])).map(e => (new Date(parseInt(e[0]))).toLocaleDateString('en-US', {month: '2-digit',day: '2-digit'})))
const line_data = computed(() => {
  const labels = allTimestamps.value.map(t =>
    new Date(t).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit' })
  )

  const datasets = Array.from(activeScales.value).map(scale => {
    let dailyMap: Record<number, number> = {}
    if (scale === 'expenses') dailyMap = dailyExpenses.value
    else if (scale === 'income')    dailyMap = dailyIncome.value
    else if (scale === 'totals')    dailyMap = dailyTotals.value

    // Apply the "ensure horizontal line" transformation
    const data = ensureEnoughPoints(dailyMap, allTimestamps.value)

    return {
      label: scale.toUpperCase(),
      data: data,
      borderColor: colorMap[scale],
      tension: 0.1,
      fill: false,
      spanGaps: scale === 'totals',          // still connect balance across gaps
      pointRadius: data.length <= 2 ? 4 : 3, // bigger dots when few points
      pointHoverRadius: 6,
    }
  })
  console.log(props.entry_data.totals)
  return { labels, datasets }
})
// 3. Reactive Bar Data
const bar_data = computed(() => {
  const activeKeys = Array.from(activeScales.value)
  
  // GUARD: Check if entry_data lists exist before mapping
  if (!props.entry_data.expenses || !props.entry_data.income || !props.entry_data.totals) {
    return { labels: [], datasets: [] }
  }

  return {
    labels: activeKeys.map(k => k.toUpperCase()),
    datasets: [{
      data: activeKeys.map(scale => {
        if (scale === 'totals') return props.entry_data.totals.at(-1)?.amount || 0
        
        // Ensure we pass an array to your helper function
        const sourceArray = props.entry_data[scale] || []
        return Object.values(sumOfDailyEntries(sourceArray)).reduce((a, b) => a + b, 0)
      }),
      backgroundColor: activeKeys.map(scale => colorMap[scale])
    }]
  }
})

const options = {
  responsive: true,
  maintainAspectRatio: true,
  borderRadius: 8.0,
  cutout: 50.0,
  plugins: {
    legend: {
      display: false,
    }
  }
}

const isSendingReport = ref(false)
const hasData = computed(() => 
  props.entry_data?.totals?.length > 0 || 
  props.entry_data?.income?.length > 0 || 
  props.entry_data?.expenses?.length > 0
)

const reportPeriod = ref<string>()
const periodOptions = [
  { label: 'Last 7 days', value: 'last-7-days' },
  { label: 'Last 30 days', value: 'last-30-days' },
  { label: 'This month',   value: 'this-month' },
  { label: 'Last month',   value: 'last-month' }
]
const toast = useToast()

const sendReport = async () => {
  // 1. FIX: Supabase uses user.value.id, not user.value.sub!
  const userId = user.value?.id || user.value?.sub; 
  console.log('User ID:', userId);

  if (!userId || !user.value?.email) {
    toast.add({
      title: 'Error',
      description: 'You must be logged in to send a report',
      color: 'neutral',
      ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
      close: {class: 'text-white'}
    })
    return
  }

  if (!hasData.value) {
    toast.add({
      title: 'Error',
      description: 'No financial data available for this period',
      color: 'neutral',
      ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
      close: {class: 'text-white'}
    })
    return
  }

  isSendingReport.value = true

  const session = useSupabaseSession();
  const accessToken = session.value?.access_token;

  if (!accessToken) {
    toast.add({ title: 'Error', description: 'Not logged in', color: 'error' });
    isSendingReport.value = false; // Stop the loading spinner
    return;
  }
  
  try {
    const res = await fetch('/api/report', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        userId: userId, // Using the corrected ID here
        email: user.value.email,
        period: reportPeriod.value,
        accessToken: accessToken 
      })
    })

    const rawText = await res.text()
    console.log('Status:', res.status)
    console.log('Raw response body:', rawText.substring(0, 400)) 

    let json
    try {
      json = JSON.parse(rawText)
    } catch (parseErr) {
      console.error('Parse failed → not JSON:', parseErr)
      throw new Error(`Server returned non-JSON: ${rawText.substring(0, 100)}...`)
    }

    if (json.success) {
      toast.add({
        title: 'Success',
        description: 'Report sent successfully! Check your email.',
        color: 'neutral',
        ui: {root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white'},
        close: {class: 'text-white'}
      })
      
    } else {
      console.error('Report error:', json)
      
      // 2. FIX: Intelligently extract the actual error message!
      // This stops it from just printing "true" and looks for the real message.
      const errorMessage = json.message || json.statusMessage || (typeof json.error === 'string' ? json.error : JSON.stringify(json.error)) || 'Unknown error';

      toast.add({
        title: 'Error',
        description: 'Failed to send report: ' + errorMessage,
        color: 'neutral',
        ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
        close: {class: 'text-white'}
      })
    }
  } catch (err: any) {
    console.error('Fetch error:', err)
    toast.add({
      title: 'Error',
      description: err.message || 'Error connecting to report service',
      color: 'neutral',
      ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
      close: {class: 'text-white'}
    })
  } finally {
    isSendingReport.value = false
  }
}

</script>

<style scoped>

</style>