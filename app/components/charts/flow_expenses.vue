<template>
  <Doughnut :data="data" :options="options"> </Doughnut>
</template>

<script setup lang="ts">
interface Props {
  categories: Array<string>;
  data: Array<number>;
}

const props = defineProps<Props>()

import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { Doughnut } from 'vue-chartjs'
ChartJS.register(ArcElement, Tooltip, Legend)

const data = computed(() => { return {
  labels: props.categories,
  datasets: [
    {
      backgroundColor: ['#41B883', '#E46651', '#00D8FF', '#DD1B16'],
      data: [40, 20, 80, 10]
    }
  ]
}})

const options = {
  responsive: true,
  maintainAspectRatio: false
}

</script>

<style scoped>

</style>