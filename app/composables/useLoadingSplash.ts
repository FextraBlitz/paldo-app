// composables/useCounter.ts


export const useLoadingStates = () => {
  const states = useState<Record<string, boolean>>('loading-states', () => reactive<Record<string, boolean>>({}));
  

  const isLoading = (): boolean => {
    return Object.values(states.value).some(val => val === true);
  };

  console.log(states.value)
  // Return the state and methods to interact with it
  return {
    states,
    isLoading
  };
};
