<template>
  <div v-if="Object.keys(props.category_data.income).length === 0" class="flex flex-col h-64 justify-center items-center font-bold text-4xl">
    No data to show.
  </div>
  <div class="flex flex-1 flex-col">
    <div class="flex items-center justify-center aspect-square p-4 h-[25vh]">
      <Doughnut :data="data" :options="options"> </Doughnut>
    </div>
    <div class="flex flex-col flex-1 min-h-0 overflow-y-scroll">
      <div class="flex h-16 border-t-2 last:border-b-2 items-center pl-2 " v-for="(category_value, category_name) in category_data.income">
        <UIcon :name="category_styles[category_name]?.icon" :style="{ color: category_styles[category_name]?.color }" class="text-4xl" />
        <div class="flex flex-col flex-1 p-2 pl-2 h-full">
          <div class="font-bold">{{ category_name }}</div>  
          <UProgress
            :style="`--ui-primary: ${category_styles[category_name]?.color};`",
            :model-value="category_value"
            :max="income_sum"
            :ui="{
              root: 'flex-1',
              base: 'rounded-none h-full',
              indicator: 'rounded-none'
            }"
          />
        </div>
        <div class="flex flex-col p-2 h-full w-24 border-l-2 border-l-black items-end justify-center">
          <div class="text-green-500"> ₱ {{ category_value }} </div>
          <div> {{ (category_value/income_sum*100).toFixed(2) }}% </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">

import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { Doughnut } from 'vue-chartjs'
ChartJS.register(ArcElement, Tooltip, Legend)

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
const category_names = computed(() => Object.keys(props.category_data.income))
const category_values = computed(() => Object.values(props.category_data.income))
const income_sum = computed(() => {
  return category_values.value.reduce((sum, currentValue) => sum + currentValue,0)
})
const { styles: category_styles, refresh: refreshStyles, pending: pendingCategoryStyles } = useCategoryStyles()

const category_colors = computed<string[]>(() => {
  console.log('ii', props.category_data.income)
  return category_names.value.map((e) => category_styles.value[e]!.color);
})

const data = computed(() => { return {
  labels: category_names.value,
  datasets: [
    {
      backgroundColor: category_colors.value,
      data: category_values.value,
    }
  ]
}})

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