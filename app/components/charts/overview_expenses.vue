<template>
  <div class="flex items-center justify-center aspect-square p-4">
    <Doughnut :data="data" :options="options"> </Doughnut>
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
}
const props = defineProps<Props>()
const category_names = ref<string[]>(Object.keys(props.category_data.expenses))
const category_values = ref<number[]>(Object.values(props.category_data.expenses))
const { styles: category_styles, pending: pendingCategoryStyles } = useCategoryStyles()

const category_colors = computed<string[]>(() => {
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