<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="lex-page-eyebrow">{{ t('payment.eyebrow') }}</p>
                        <h1 class="lex-page-title">{{ payment ? formatAmount(payment.amount) : '...' }}</h1>
                        <p v-if="payment?.description" class="lex-page-copy lex-page-copy-full text-sm">
                            {{ payment.description }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <RouterLink
                            v-if="canEdit && payment"
                            :to="{ name: 'payment-edit', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid, payment: paymentUlid } }"
                            class="lex-button lex-button-primary"
                        >
                            {{ t('payment.edit') }}
                        </RouterLink>
                        <button
                            v-if="canEdit && payment"
                            type="button"
                            class="lex-button lex-button-inline lex-button-inline-danger"
                            @click="pendingDelete = true"
                        >
                            {{ t('payment.delete') }}
                        </button>
                        <RouterLink
                            :to="{ name: 'payments', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid } }"
                            class="lex-button lex-button-secondary"
                        >
                            {{ t('payment.back') }}
                        </RouterLink>
                    </div>
                </div>
            </section>

            <div v-if="loading" class="lex-panel p-8 text-sm text-slate-500">
                {{ t('payment.loading') }}
            </div>

            <div v-else-if="loadError" class="lex-panel p-8">
                <p class="lex-form-message lex-form-message-error">{{ t('payment.load_error') }}</p>
            </div>

            <template v-else-if="payment">
                <SectionPanel>
                    <dl class="grid gap-5 sm:grid-cols-3">
                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ t('payment.amount_label') }}
                            </dt>
                            <dd class="text-sm text-slate-800">{{ formatAmount(payment.amount) }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ t('payment.date_label') }}
                            </dt>
                            <dd class="text-sm text-slate-800">
                                {{ payment.date ? formatDate(payment.date) : t('payment.none') }}
                            </dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ t('payment.description_label') }}
                            </dt>
                            <dd class="text-sm text-slate-800">{{ payment.description || t('payment.none') }}</dd>
                        </div>
                    </dl>
                </SectionPanel>
            </template>
        </div>

        <ConfirmationDialog
            :open="pendingDelete"
            :eyebrow="t('payment.confirm_delete_eyebrow')"
            :title="t('payment.confirm_delete_title')"
            :message="t('payment.confirm_delete_message')"
            :confirm-label="t('payment.confirm_delete')"
            :cancel-label="t('payment.cancel_label')"
            pending-label="..."
            :submitting="deleting"
            @cancel="pendingDelete = false"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import ConfirmationDialog from '@/components/ui/ConfirmationDialog.vue';
import SectionPanel from '@/components/ui/SectionPanel.vue';
import { fetchPayment, deletePayment, type Payment } from '@/api/payments';
import { fetchDebtor, debtorDisplayName } from '@/api/debtors';
import { fetchDebtDetail } from '@/api/debts';
import { fetchDistrictStats } from '@/api/districts';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import { useUserFormatting } from '@/composables/useUserFormatting';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const breadcrumbStore = useBreadcrumbStore();
const { formatDate, formatAmount } = useUserFormatting();

const districtUlid = computed(() => String(route.params.district ?? ''));
const debtorUlid = computed(() => String(route.params.debtor ?? ''));
const debtUlid = computed(() => String(route.params.debt ?? ''));
const paymentUlid = computed(() => String(route.params.payment ?? ''));

const loading = ref(true);
const loadError = ref(false);
const payment = ref<Payment | null>(null);
const canEdit = ref(false);
const pendingDelete = ref(false);
const deleting = ref(false);

async function confirmDelete(): Promise<void> {
    deleting.value = true;

    try {
        await deletePayment(districtUlid.value, debtorUlid.value, debtUlid.value, paymentUlid.value);
        await router.push({ name: 'payments', params: { district: districtUlid.value, debtor: debtorUlid.value, debt: debtUlid.value } });
    } catch {
        pendingDelete.value = false;
    } finally {
        deleting.value = false;
    }
}

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const [paymentData, debtorData, debtDetail, statsData] = await Promise.all([
            fetchPayment(districtUlid.value, debtorUlid.value, debtUlid.value, paymentUlid.value),
            fetchDebtor(districtUlid.value, debtorUlid.value),
            fetchDebtDetail(districtUlid.value, debtorUlid.value, debtUlid.value),
            fetchDistrictStats(districtUlid.value),
        ]);

        payment.value = paymentData;
        canEdit.value = statsData.can_create_payment;

        const debtBreadcrumb = debtorData.case_number
            ? t('debt_detail.title', { number: debtorData.case_number })
            : debtDetail.debt.description || t('debt_detail.title_fallback');

        breadcrumbStore.districtLabel = t('district.number_label', { number: statsData.district.number });
        breadcrumbStore.debtorLabel = debtorDisplayName(debtorData);
        breadcrumbStore.debtLabel = debtBreadcrumb;
        breadcrumbStore.paymentLabel = formatAmount(paymentData.amount);
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);

onUnmounted(() => {
    breadcrumbStore.debtorLabel = null;
    breadcrumbStore.debtLabel = null;
    breadcrumbStore.districtLabel = null;
    breadcrumbStore.paymentLabel = null;
});
</script>
