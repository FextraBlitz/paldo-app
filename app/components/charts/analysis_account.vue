<template>
  <div v-if="Object.keys(props.entry_data).length === 0" class="flex flex-col h-64 justify-center items-center font-bold text-4xl">
    No data to show.
  </div>
  <div class="flex flex-1 flex-col">
    <div class="flex items-center justify-center aspect-square p-4 h-[25vh]">
      <Line v-if="activeChart == 'time'" :data="line_data" :options="options"> </Line>
      <Bar v-if="activeChart == 'total'" :data="bar_data" :options="options"> </Bar>
      
    </div>
    <section class="flex flex-wrap *:flex-1 *:ring-1 *:rounded-none *:ring-black *:justify-center *:text-white">
      <UButton @click="switchChart('total')" :active="activeChart === 'total'" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> TOTAL </UButton>
      <UButton @click="switchChart('time')" :active="activeChart === 'time'" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> TIME </UButton>
    </section>
    <section class="flex *:flex-1 *:ring-1 *:rounded-none *:ring-black *:justify-center *:text-white">
      <UButton @click="switchScale('expenses')" :active="activeScales.has('expenses')" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> EXPENSES </UButton>
      <UButton @click="switchScale('totals')" :active="activeScales.has('totals')" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> TOTAL </UButton>
      <UButton @click="switchScale('income')" :active="activeScales.has('income')" class="bg-blue-500 active:bg-blue-600 hover:bg-blue-600" active-class="font-bold !bg-blue-600"> INCOME </UButton>
    </section>
    <section class="flex justify-center gap-4 py-3 border-b border-black text-[10px] font-bold uppercase">
      <div 
        v-for="scale in ['income', 'total', 'expenses']" 
        :key="scale"
        class="flex items-center gap-1 transition-opacity duration-200"
        :class="activeScales.has(scale as scaleMode) ? 'opacity-100' : 'opacity-20'"
      >
        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: colorMap[scale as scaleMode] }"></span>
        <span>{{ scale }}</span>
      </div>
    </section>
    
  </div>
</template>

<script setup lang="ts">

import { Chart as ChartJS, Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, LineController, BarElement, BarController } from 'chart.js'
import { Line, Bar } from 'vue-chartjs'
ChartJS.register(Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, LineController, BarElement, BarController )

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

const activeChart = ref<chartMode>('total')
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


// --- LINE CHART DATA (TIME) ---
// 2. Reactive Line Data
const line_data = computed(() => ({
  labels: props.entry_data.totals.map(e => e.id),
  datasets: Array.from(activeScales.value).map(scale => ({
    label: scale.toUpperCase(),
    data: props.entry_data[scale].map(e => e.amount),
    borderColor: colorMap[scale],
    tension: 0.1,
    fill: false
  }))
}))

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

</script>

<style scoped>

</style>