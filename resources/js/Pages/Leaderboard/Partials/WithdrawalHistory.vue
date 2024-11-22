<script setup>
import Dialog from "primevue/dialog";
import Button from "@/Components/Button.vue";
// import Tooltip from "@/Components/Tooltip.vue";
import { ref, watch } from "vue";
import { IconX, IconClock, IconDownload } from "@tabler/icons-vue";
import { transactionFormat } from "@/Composables/index.js";
import DatePicker from 'primevue/datepicker';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import dayjs from 'dayjs'
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import { trans, wTrans } from "laravel-vue-i18n";


const visible = ref(false);
const visible2 = ref(false);

const closeDialog = () => {
    visible.value = false;
}

const { formatAmount, formatDate } = transactionFormat();

const loading = ref(false);
const dt = ref();
const withdrawals = ref()
const totalWithdrawalAmount = ref(0);


const today = new Date();

// Define minDate and maxDate
const minDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const maxDate = ref(today);
const selectedDate = ref([minDate.value, maxDate.value]);

const getResults = async (dateRanges = null) => {
    loading.value = true;

    try {
        const params = new URLSearchParams();

        if (dateRanges) {
            const [startDate, endDate] = dateRanges;
            params.append('startDate', dayjs(startDate).format('YYYY-MM-DD'));
            params.append('endDate', dayjs(endDate).format('YYYY-MM-DD'));
        }

        const response = await axios.get('/leaderboard/getWithdrawalHistory', { params });
        withdrawals.value = response.data.withdrawals;
        totalWithdrawalAmount.value = response.data.totalWithdrawalAmount;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;

        if (startDate && endDate) {
            getResults([startDate, endDate]);
        } else if (startDate || endDate) {
            getResults([startDate || endDate, endDate || startDate]);
        } else {
            getResults(null);
        }
    } else if (newDateRange === null) {
        getResults(null);
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

const clearDate = () => {
    selectedDate.value = null;
};

const exportCSV = () => {
    const dtComponent = dt.value;

    // Manually specify the fields to include in the CSV export
    const exportFields = [
        { field: 'created_at', header: wTrans('public.date') },
        { field: 'transaction_number', header: wTrans('public.id') },
        { field: 'incentive_amount', header: `${wTrans('public.amount')} ($)` },
        { field: 'status', header: wTrans('public.status') },
    ];

    dtComponent.exportCSV({
        exportColumns: exportFields, // Specify columns for export
    });
};

const openDialog = (rowData) => {
    visible2.value = true;
    data.value = rowData;
};

</script>

<template>
    <Button
        type="button"
        class="!px-3"
        variant="gray-outlined"
        @click="visible = true"
    >
        <IconClock aria-hidden="true" class="w-5 h-5" />
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.incentive_withdrawal_history')"
        class="dialog-xs md:dialog-md"
    >
        <div class="pt-6">
            <DataTable
                :value="withdrawals"
                removableSort
                scrollable
                scrollHeight="400px"
                tableStyle="md:min-width: 50rem"
                ref="dt"
                :loading="loading"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col items-center gap-4 mb-4 md:mb-8">
                        <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:justify-between">
                            <div class="relative w-full md:w-60">
                                <DatePicker 
                                    v-model="selectedDate"
                                    selectionMode="range"
                                    :manualInput="false"
                                    :maxDate="maxDate"
                                    dateFormat="dd/mm/yy"
                                    showIcon
                                    iconDisplay="input"
                                    :placeholder="$t('public.select_date')"
                                    class="font-normal w-full md:w-60"
                                />
                                <div
                                    v-if="selectedDate && selectedDate.length > 0"
                                    class="absolute top-[11px] right-3 flex justify-center items-center text-gray-400 select-none cursor-pointer bg-white w-6 h-6 "
                                    @click="clearDate"
                                >
                                    <IconX size="20" />
                                </div>
                            </div>

                            <Button
                                variant="primary-outlined"
                                @click="exportCSV($event)"
                                class="w-full md:w-auto"
                            >
                                <IconDownload size="20" stroke-width="1.25" />
                                {{ $t('public.export') }}
                            </Button>
                        </div>
                        <div v-if="withdrawals?.length > 0" class="flex items-center gap-3 self-stretch md:hidden">
                            <span class="text-gray-500 text-sm font-normal">{{ $t('public.total') }}:</span>
                            <span class="text-gray-950 font-semibold">$&nbsp;{{ formatAmount(totalWithdrawalAmount) }}</span>
                        </div>
                    </div>
                </template>
                <template #empty><Empty :title="$t('public.empty_incentive_withdrawal_title')" :message="$t('public.empty_incentive_withdrawal_message')" /></template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="withdrawals?.length > 0">
                <!-- <template> -->
                    <Column field="created_at" :header="$t('public.date')" sortable style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column field="transaction_number" :header="$t('public.id')" sortable style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ slotProps.data.transaction_number }}
                        </template>
                    </Column>
                    <Column field="amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ formatAmount(slotProps.data.amount) }}
                        </template>
                    </Column>
                    <Column field="status" :header="$t('public.status')" sortable style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ $t(`public.${slotProps.data.status}`) }}
                        </template>
                    </Column>
                    <ColumnGroup type="footer">
                        <Row>
                            <Column class="hidden md:table-cell" :footer="$t('public.total_approved') + ' ($) :'" :colspan="4" footerStyle="text-align:right" />
                            <Column class="hidden md:table-cell" :footer="formatAmount(totalWithdrawalAmount ? totalWithdrawalAmount : 0)" />
                        </Row>
                    </ColumnGroup>
                    <!-- <Column style="width: 50%" class="md:hidden" headerClass="hidden">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start gap-1 self-stretch">
                                <span class="self-stretch text-gray-950 font-semibold">{{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}</span>
                                <div class="flex items-center gap-1 text-gray-700 text-xs">
                                    <div>
                                        <span v-if="profile.sales_category !== 'trade_volume'">$&nbsp;</span>{{ formatAmount(slotProps.data.achieved_amount) }}<span v-if="profile.sales_category === 'trade_volume'">&nbsp;Ł</span>
                                    </div>
                                    <span class="text-gray-500">|</span>
                                    <div>
                                        <span v-if="profile.sales_category !== 'trade_volume'">$&nbsp;</span>{{ formatAmount(slotProps.data.target_amount) }}<span v-if="profile.sales_category === 'trade_volume'">&nbsp;Ł</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column style="width: 50%" class="md:hidden" headerClass="hidden">
                        <template #body="slotProps">
                            <div class="flex flex-col items-end gap-1">
                                <span class="self-stretch text-gray-950 text-right font-semibold">$&nbsp;{{ formatAmount(slotProps.data.incentive_amount) }}</span>
                                <div class="flex justify-end items-center gap-1 self-stretch text-xs">
                                    <span class="text-gray-700">{{ $t('public.rate') }}:</span>
                                    <div class="text-gray-500 text-right">
                                        <span v-if="profile.sales_category === 'trade_volume'">$&nbsp;</span>{{ formatAmount(slotProps.data.incentive_rate) }}<span v-if="profile.sales_category !== 'trade_volume'">&nbsp;%</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column> -->
                </template>
            </DataTable>
        </div>
    </Dialog>

    <Dialog v-model:visible="visible2" modal :header="$t('public.details')" class="dialog-xs md:dialog-md">
        <div class="flex flex-col justify-center items-center gap-3 self-stretch pt-4 md:pt-6">
            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ dayjs(data.created_at).format('YYYY/MM/DD') }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.transaction_id') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.transaction_number }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.description') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ $t('public.incentive_withdrawal') }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm" >{{ $t('public.amount') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ `$&nbsp;${formatAmount(data.amount)}` }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.status') }}</span>
                    <span class="px-2 py-1 truncate text-white text-sm font-medium rounded-sm" :style="{ backgroundColor: getStatusColor(data.status) }">{{ $t(`public.${data.status}`) }}</span>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.wallet_name') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.payment_account_name }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.receiving_address') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.account_no }}</span>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.remarks') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.comment }}</span>
                </div>
            </div>
        </div>
    </Dialog>
</template>