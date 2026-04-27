<template>
    <AppLayout>
        <div class="lex-overview-page">
            <section class="lex-panel lex-panel-header p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="lex-page-eyebrow">{{ t('payment_list.eyebrow') }}</p>
                        <h1 class="lex-page-title">{{ pageTitle }}</h1>
                        <p v-if="debtorName" class="lex-page-copy lex-page-copy-full text-sm">
                            {{ debtorName }}
                        </p>
                    </div>

                    <RouterLink
                        :to="{ name: 'debt', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid } }"
                        class="lex-button lex-button-secondary"
                    >
                        {{ t('payment_list.back') }}
                    </RouterLink>
                </div>
            </section>

            <div v-if="loading" class="lex-panel p-8 text-sm text-slate-500">
                {{ t('payment_list.loading') }}
            </div>

            <div v-else-if="loadError" class="lex-panel p-8">
                <p class="lex-form-message lex-form-message-error">{{ t('payment_list.load_error') }}</p>
            </div>

            <template v-else>
                <SectionPanel :title="t('payment_list.list_title')">
                    <template v-if="canCreatePayment" #actions>
                        <RouterLink
                            :to="{ name: 'payment-create', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid } }"
                            class="lex-button lex-button-primary"
                        >
                            {{ t('payment_list.new_payment') }}
                        </RouterLink>
                    </template>

                    <DataTable
                        :columns="paymentColumns"
                        :rows="payments"
                        row-key="ulid"
                        row-id="ulid"
                    >
                        <template #cell-date="{ row }">
                            {{ row.date ? formatDate(row.date) : t('payment_list.none') }}
                        </template>
                        <template #cell-amount="{ row }">
                            {{ formatAmount(row.amount) }}
                        </template>
                        <template #cell-description="{ row }">
                            {{ row.description || t('payment_list.none') }}
                        </template>
                        <template #actions="{ row }">
                            <div class="flex gap-2">
                                <RouterLink
                                    :to="{ name: 'payment-show', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid, payment: row.ulid } }"
                                    class="lex-button lex-button-secondary"
                                >
                                    {{ t('payment_list.view') }}
                                </RouterLink>
                                <RouterLink
                                    v-if="canCreatePayment"
                                    :to="{ name: 'payment-edit', params: { district: districtUlid, debtor: debtorUlid, debt: debtUlid, payment: row.ulid } }"
                                    class="lex-button lex-button-secondary"
                                >
                                    {{ t('payment_list.edit') }}
                                </RouterLink>
                                <button
                                    v-if="canCreatePayment"
                                    type="button"
                                    class="lex-button lex-button-inline lex-button-inline-danger"
                                    @click="requestDelete(row)"
                                >
                                    {{ t('payment_list.delete') }}
                                </button>
                            </div>
                        </template>
                        <template #empty>{{ t('payment_list.no_payments') }}</template>
                    </DataTable>
                </SectionPanel>
            </template>
        </div>

        <ConfirmationDialog
            :open="pendingDeleteUlid !== null"
            :eyebrow="t('payment_list.confirm_delete_eyebrow')"
            :title="t('payment_list.confirm_delete_title')"
            :message="t('payment_list.confirm_delete_message')"
            :confirm-label="t('payment_list.confirm_delete')"
            :cancel-label="t('payment_list.cancel_label')"
            pending-label="..."
            :submitting="deleting"
            @cancel="pendingDeleteUlid = null"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import ConfirmationDialog from '@/components/ui/ConfirmationDialog.vue';
import DataTable from '@/components/ui/DataTable.vue';
import type { TableColumn } from '@/components/ui/DataTable.vue';
import SectionPanel from '@/components/ui/SectionPanel.vue';
import { listPayments, deletePayment, type Payment } from '@/api/payments';
import { fetchDebtor, debtorDisplayName } from '@/api/debtors';
import { fetchDebtDetail } from '@/api/debts';
import { fetchDistrictStats } from '@/api/districts';
import { useBreadcrumbStore } from '@/stores/breadcrumb';
import { useUserFormatting } from '@/composables/useUserFormatting';

const { t } = useI18n();
const route = useRoute();
const breadcrumbStore = useBreadcrumbStore();
const { formatDate, formatAmount } = useUserFormatting();

const paymentColumns = computed((): TableColumn<Payment>[] => [
    { key: 'date', label: t('payment_list.columns.date'), sortable: true, filterable: true },
    { key: 'amount', label: t('payment_list.columns.amount'), sortable: true, align: 'right' },
    { key: 'description', label: t('payment_list.columns.description'), filterable: true },
]);

const districtUlid = computed(() => String(route.params.district ?? ''));
const debtorUlid = computed(() => String(route.params.debtor ?? ''));
const debtUlid = computed(() => String(route.params.debt ?? ''));

const loading = ref(true);
const loadError = ref(false);
const payments = ref<Payment[]>([]);
const debtorName = ref<string | null>(null);
const debtDescription = ref<string | null>(null);
const caseNumber = ref<string | null>(null);
const canCreatePayment = ref(false);

const pendingDeleteUlid = ref<string | null>(null);
const deleting = ref(false);

const pageTitle = computed(() => {
    if (caseNumber.value) {
        return t('debt_detail.title', { number: caseNumber.value });
    }

    if (debtDescription.value) {
        return debtDescription.value;
    }

    return t('payment_list.eyebrow');
});

function sortPayments(list: Payment[]): Payment[] {
    return [...list].sort((a, b) => {
        if (a.date === b.date) {
            return a.ulid < b.ulid ? 1 : -1;
        }

        return (a.date ?? '') < (b.date ?? '') ? 1 : -1;
    });
}

function requestDelete(payment: Payment): void {
    pendingDeleteUlid.value = payment.ulid;
}

async function confirmDelete(): Promise<void> {
    if (pendingDeleteUlid.value === null) {
        return;
    }

    deleting.value = true;

    try {
        await deletePayment(districtUlid.value, debtorUlid.value, debtUlid.value, pendingDeleteUlid.value);
        payments.value = payments.value.filter((p) => p.ulid !== pendingDeleteUlid.value);
        pendingDeleteUlid.value = null;
    } catch {
        pendingDeleteUlid.value = null;
    } finally {
        deleting.value = false;
    }
}

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const [paymentList, debtorData, debtDetail, statsData] = await Promise.all([
            listPayments(districtUlid.value, debtorUlid.value, debtUlid.value),
            fetchDebtor(districtUlid.value, debtorUlid.value),
            fetchDebtDetail(districtUlid.value, debtorUlid.value, debtUlid.value),
            fetchDistrictStats(districtUlid.value),
        ]);

        payments.value = sortPayments(paymentList);
        debtorName.value = debtorDisplayName(debtorData);
        debtDescription.value = debtDetail.debt.description;
        caseNumber.value = debtorData.case_number;
        canCreatePayment.value = statsData.can_create_payment;

        const debtBreadcrumb = debtorData.case_number
            ? t('debt_detail.title', { number: debtorData.case_number })
            : debtDetail.debt.description || t('debt_detail.title_fallback');

        breadcrumbStore.districtLabel = t('district.number_label', { number: statsData.district.number });
        breadcrumbStore.debtorLabel = debtorDisplayName(debtorData);
        breadcrumbStore.debtLabel = debtBreadcrumb;
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
});
</script>
