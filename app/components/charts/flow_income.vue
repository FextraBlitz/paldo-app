<template>
  <div v-if="Object.keys(props.entry_data).length === 0" class="flex flex-col h-64 justify-center items-center font-bold text-4xl">
    No data to show.
  </div>
  <div v-else class="flex flex-1 flex-col">
    <div class="flex items-center justify-center aspect-square p-4 h-[25vh]">
      <Line :data="data" :options="options"> </Line>
    </div>
    <UPopover arrow :reference="popover_reference" :open="checkPopover" :content="{ side: 'top', sideOffset: 8, updatePositionStrategy: 'always' }"
      :ui="{
        content: 'flex flex-col items-center justify-center p-2 rounded-2 ring-1 ring-black bg-white text-black',
        arrow: 'fill-black stroke-black'
      }"
    >
      <template #content>
        <div class="text-lg"> Income: {{ sorted_daily_incomes[msToDay(selectedDate?.toDate('UTC').getTime() ?? 0)] }}
        </div>
      </template>
      <UCalendar @vue:updated="onSelectDate" ref="calendar-ref" v-model="selectedDate" :month-controls="false" :year-controls="false"
        :ui="{
          root: 'w-full'
        }"
      >
        <template #week-day="{ day }">
          <div class="text-blue-500">{{ day }}</div>
        </template>
        <template #day="{ day }">
          <UChip :show="dates.has(msToDay(day.toDate('UTC').getTime()))" color="secondary" size="2xs">
            <div class="mx-auto w-full">{{ day.day }}</div>
          </UChip>
        </template>
      </UCalendar>
    </UPopover>
    <!-- <div> {{ selectedDate?.toDate('UTC').getTime() }} {{ selectedDate }}</div> -->
  </div>
</template>
<script setup lang="ts">

import { Chart as ChartJS, Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, LineController } from 'chart.js'
import { Line } from 'vue-chartjs'
ChartJS.register(Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, LineController)
import { CalendarDate, today, getLocalTimeZone, type DateValue } from '@internationalized/date'
import { CalendarCellTrigger } from 'reka-ui';

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

//const dates = ref(new Set(props.entry_data.income.map((income) => msToDay(income.date.getTime()))))
const dates = computed(() => new Set(props.entry_data.income.map((income) => msToDay(income.date.getTime()))))
const selectedDate = ref<DateValue>()
const calendarRef = useTemplateRef<ComponentPublicInstance>('calendar-ref')

// const openPopover = ref(false)
const anchor = ref({ x: 0, y: 0 })
const checkPopover = computed(() => {
  return dates.value.has(msToDay(selectedDate.value?.toDate('UTC').getTime() ?? 0))
})

const onSelectDate = () => {
  const selectedElement = (calendarRef.value?.$el as HTMLElement).querySelector('[data-selected]');
  // console.log(selectedElement)
  if (selectedElement){
    const date_rect = selectedElement.getBoundingClientRect();
    [anchor.value.x, anchor.value.y] = [date_rect.x + date_rect.width / 2, date_rect.y]
  }
  return true;
}

const popover_reference = computed(() => ({
  getBoundingClientRect: () =>
    ({
      width: 0,
      height: 0,
      left: anchor.value.x,
      right: anchor.value.x,
      top: anchor.value.y,
      bottom: anchor.value.y,
      ...anchor.value
    } as DOMRect)
}))

//const sorted_daily_incomes = computed(() => sortDailyEntries(sumOfDailyEntries(props.entry_data.income)))
const sorted_daily_incomes = computed(() => sortDailyEntries(sumOfDailyEntries(props.entry_data.income)))
//const labels = Object.keys(sorted_daily_incomes.value).map((entry_date) => (new Date(parseInt(entry_date))).toLocaleString('en-US', {month: 'short', day:'2-digit'}));
const data = computed(() => {
  const labels = Object.keys(sorted_daily_incomes.value).map((entry_date) => (new Date(parseInt(entry_date))).toLocaleString('en-US', {month: 'short', day:'2-digit'}));
  return {
    labels: labels,
    datasets: [
      {
        label: 'Income',
        data: Object.values(sorted_daily_incomes.value),
        fill: false,
        borderColor: 'oklch(62.3% 0.214 259.815)',
        tension: 0.1
      },
    ],
  };
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