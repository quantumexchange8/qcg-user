<script setup>
import Dialog from "primevue/dialog";
import Button from "@/Components/Button.vue";
// import Tooltip from "@/Components/Tooltip.vue";
import { computed, ref, watch } from "vue";
import { IconX, IconReport, IconDownload } from "@tabler/icons-vue";
import { transactionFormat } from "@/Composables/index.js";
import DatePicker from 'primevue/datepicker';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import debounce from "lodash/debounce.js";
import Select from 'primevue/select';
import dayjs from 'dayjs'
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import { trans, wTrans } from "laravel-vue-i18n";
import StatusBadge from '@/Components/StatusBadge.vue';

const visible = ref(false);
const visible2 = ref(false);

const closeDialog = () => {
    visible.value = false;
}

const { formatAmount, formatDate } = transactionFormat();

const loading = ref(false);
const descriptions = [
    { name: wTrans('public.all'), value: 'all' },
    { name: wTrans('public.rebate_payout'), value: 'rebate_payout' },
    { name: wTrans('public.apply_rebate'), value: 'apply_rebate' },
    { name: wTrans('public.transfer'), value: 'transfer' },
    { name: wTrans('public.withdrawal'), value: 'withdrawal' }
];
const selectedDescription = ref(descriptions[0].value);
const dt = ref();
const rebate = ref();

const today = new Date();

// Define minDate and maxDate
const minDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const maxDate = ref(today);
const selectedDate = ref([minDate.value, maxDate.value]);

const getResults = async (description = '', dateRanges = null) => {
    loading.value = true;

    try {
        const params = new URLSearchParams();

        if (description) {
            params.append('description', description);
        }

        if (dateRanges) {
            const [startDate, endDate] = dateRanges;
            params.append('startDate', dayjs(startDate).format('YYYY-MM-DD'));
            params.append('endDate', dayjs(endDate).format('YYYY-MM-DD'));
        }
        
        // console.log(description)
        const response = await axios.get('/dashboard/getRebateTransactions', { params });
        // console.log('Params:', params.toString());
        // console.log(response.data);
        rebate.value = response.data.transactions;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

getResults(selectedDescription.value, selectedDate.value);

watch(
    [selectedDescription, selectedDate],
    debounce(([descriptionValue, dateRange]) => {
        if (Array.isArray(dateRange)) {
            const [startDate, endDate] = dateRange;

            if (startDate && endDate) {
                getResults(descriptionValue, [startDate, endDate]);
            } else if (startDate || endDate) {
                getResults(descriptionValue, [startDate || endDate, endDate || startDate]);
            } else {
                getResults(descriptionValue, null);
            }
        } else if (dateRange === null) {
            getResults(descriptionValue, null);
        } else {
            console.warn('Invalid date range format:', dateRange);
        }
    }, 300)
);

const clearDate = () => {
    selectedDate.value = null;
};

// const getStatusColor = (status) => {
//   switch(status.toLowerCase()) {
//     case 'successful':
//       return '#22C55E'; // Green
//     case 'processing':
//       return '#0EA5E9'; // Blue
//     case 'failed':
//       return '#DC2626'; // Red
//     case 'rejected':
//       return '#DC2626'; // Red
//     default:
//       return 'transparent'; // Default or undefined status
//   }
// };

const getAmountTextColor = (transactionType) => {
    if (['rebate_payout', 'rebate_in'].includes(transactionType)) {
        return 'text-success-600'; 
    } else {
        return 'text-error-600'; 
    }
};

// dialog
const data = ref({});
const openDialog = (rowData) => {
    visible2.value = true;
    data.value = rowData;
};

</script>

<template>
    <Button
        variant="gray-outlined"
        type="button"
        size="sm"
        iconOnly
        pill
        @click="visible = true"
    >
        <IconReport size="16" stroke-width="1.5" />
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.rebate_history')"
        class="dialog-xs md:dialog-md"
    >
        <div class="pt-6">
            <DataTable
                :value="rebate"
                removableSort
                :paginator="rebate?.length > 0"
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                ref="dt"
                selectionMode="single"
                :loading="loading"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col items-center gap-4 mb-4 md:mb-8">
                        <div class="flex flex-col items-center gap-5 self-stretch md:flex-row">
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

                            <Select 
                                v-model="selectedDescription"
                                :options="descriptions"
                                optionLabel="name"
                                optionValue="value"
                                class="w-full font-normal md:w-60"
                            />
                        </div>
                    </div>
                </template>
                <template #empty><Empty :title="$t('public.empty_rebate_history_title')" :message="$t('public.empty_rebate_history_message')" /></template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="rebate?.length > 0">
                <!-- <template> -->
                    <Column field="created_at" :header="$t('public.date')" sortable style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column field="transaction_number" :header="$t('public.id')" sortable style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ slotProps.data.transaction_number }}
                        </template>
                    </Column>
                    <Column field="description" :header="$t('public.description')" style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <template v-if="slotProps.data.transaction_type === 'transfer_to_account'">
                                {{ $t('public.to') }} {{ slotProps.data.to_meta_login }}
                            </template>
                            <template v-else>
                                {{ $t(`public.${slotProps.data.transaction_type}`) }}
                            </template>
                        </template>
                    </Column>
                    <Column field="amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <span :class="getAmountTextColor(slotProps.data.transaction_type)">
                                {{ formatAmount(slotProps.data.amount) }}
                            </span>
                        </template>
                    </Column>
                    <!-- <Column field="status" :header="$t('public.status')" style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="flex py-1.5 items-center flex-1">
                                <StatusBadge :variant="slotProps.data.status">
                                    {{ $t(`public.${slotProps.data.status}`) }}
                                </StatusBadge>
                            </div>
                        </template>
                    </Column> -->
                    <Column class="md:hidden">
                        <template #body="slotProps">
                            <div class="flex items-center justify-between">
                                <div class="flex flex-col items-start">
                                    <div class="text-sm font-semibold">
                                        {{ $t(`public.${slotProps.data.transaction_type}`) }}
                                    </div>
                                    <div class="text-gray-500 text-xs">
                                        {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                                    </div>
                                </div>
                                <div class="overflow-hidden text-right text-ellipsis font-semibold" :class="getAmountTextColor(slotProps.data.transaction_type)">
                                    $&nbsp;{{ formatAmount(slotProps.data.amount) }}
                                </div>
                            </div>
                        </template>
                    </Column>
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
                <div v-if="data.transaction_type === 'withdrawal' || data.transaction_type === 'transfer'" class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.transaction_id') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.transaction_number }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.description') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">
                        <template v-if="data.transaction_type === 'transfer_to_account'">
                            {{ $t('public.to') }} {{ data.to_meta_login }}
                        </template>
                        <template v-else>
                            {{ $t(`public.${data.transaction_type}`) }}
                        </template>
                    </span>
                </div>
                <div v-if="data.transaction_type === 'rebate_payout'" class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.account_type') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ $t(`public.${data.account_type}`) }}</span>
                </div>
                <div v-if="data.transaction_type === 'rebate_payout'" class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm" >{{ $t('public.total_volume') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ `$&nbsp;${formatAmount(data.total_volume)} Ł` }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm" >{{ $t('public.amount') }}</span>
                    <span class="w-full truncate text-sm font-medium" :class="getAmountTextColor(data.transaction_type)">{{ `$&nbsp;${formatAmount(data.amount)}` }}</span>
                </div>
            </div>
            <!-- v-if for the 2 div below -->
            <div v-if="data.transaction_type === 'withdrawal'" class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.wallet_name') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.wallet_name }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.receiving_address') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.to_wallet_address }}</span>
                </div>
            </div>

            <div v-if="data.transaction_type === 'withdrawal' || data.transaction_type === 'rebate_in' || data.transaction_type === 'rebate_out'" class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.remarks') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.remarks }}</span>
                </div>
            </div>
        </div>
    </Dialog>
</template>