export const transaction_type_indicator = {
  "expenses": ['e'],
  "income": ['i'],
  "total": ['e', 'i'],
}

export interface FormattedEntry {
  id: number;
  catName: string;
  amount: number;
  date: Date;
  type: string;
}

export const msToDay = (ms: number) =>  {
  return ms-(ms%86400000)
}

export const groupEntriesByDay = (entries: FormattedEntry[]) => {
  const result = {} as Record<number, FormattedEntry[]>
  for (const entry of entries) {
    if (entry) {
      const time = msToDay(entry.date.getTime())
      console.log(entry ,time)
      if (!result[time]) {
        result[time] = []
      }
      result[time].push(entry)
    }
  }
  console.log(result)
  return result
}

export const sumOfDailyEntries = (entries: FormattedEntry[]) => {
  const result = {} as Record<number, number>
  const daily_entries = groupEntriesByDay(entries)
  for (const [day, entries] of Object.entries(daily_entries)) {
    result[parseInt(day)] = entries.reduce((income_sum, entry) => income_sum+entry.amount, 0)
  }
  return result
}

export const sortDailyEntries = (entries: Record<number, number>) => {
  const sorted =  Object.entries(entries).sort(
    (a, b) => {
      return parseInt(a[0]) - parseInt(b[0]); // Ascending order
    }
  )
  
  const result = {} as Record<number, number>
  for (const [day, sum] of sorted) {
    result[parseInt(day)] = sum
  }
  return result
}