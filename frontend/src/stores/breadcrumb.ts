import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useBreadcrumbStore = defineStore('breadcrumb', () => {
  const districtNumber = ref<number | null>(null);
  const districtLabel = ref<string | null>(null);
  const debtorLabel = ref<string | null>(null);
  const debtLabel = ref<string | null>(null);
  const paymentLabel = ref<string | null>(null);

  return { districtNumber, districtLabel, debtorLabel, debtLabel, paymentLabel };
});
