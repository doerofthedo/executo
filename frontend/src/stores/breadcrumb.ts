import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useBreadcrumbStore = defineStore('breadcrumb', () => {
  const districtLabel = ref<string | null>(null);
  const debtorLabel = ref<string | null>(null);
  const debtLabel = ref<string | null>(null);

  return { districtLabel, debtorLabel, debtLabel };
});
