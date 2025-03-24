<script setup>
import Dialog from "primevue/dialog";
import Button from "@/Components/Button.vue";
// import Tooltip from "@/Components/Tooltip.vue";
import { ref, watch } from "vue";
import { IconX, IconClock, IconDownload } from "@tabler/icons-vue";
import { transactionFormat } from "@/Composables/index.js";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import Select from 'primevue/select';
import dayjs from 'dayjs'
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import { trans, wTrans } from "laravel-vue-i18n";
import StatusBadge from "@/Components/StatusBadge.vue";

const visible = ref(false);
const visible2 = ref(false);

const { formatAmount, formatDate } = transactionFormat();

const loading = ref(false);
const dt = ref();
const withdrawals = ref()
const totalWithdrawalAmount = ref(0);

const months = ref([]);
const selectedMonth = ref('');

const getCurrentMonthYear = () => {
    const date = new Date();
    return `01 ${dayjs(date).format('MMMM YYYY')}`;
};

// Fetch settlement months from API
const getIncentiveMonths = async () => {
    try {
        const response = await axios.get('/getIncentiveMonths');
        months.value = response.data.months;

        if (months.value.length) {
            selectedMonth.value = getCurrentMonthYear();
        }
    } catch (error) {
        console.error('Error incentive months:', error);
    }
};

getIncentiveMonths()

const getResults = async (selectedMonth = '') => {
    loading.value = true;

    try {
        const params = new URLSearchParams();

        if (selectedMonth) {
            let formattedMonth = selectedMonth;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            params.append('selectedMonth', formattedMonth);
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

watch(selectedMonth, (newMonth) => {
    getResults(newMonth);
});

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = withdrawals.value;

    // Specify the headers
    const headers = [
        trans('public.created_at'),
        trans('public.id'),
        trans('public.amount') + ' ($)',
        trans('public.status'),
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        return [
            obj.created_at !== undefined ? dayjs(obj.created_at).format('YYYY/MM/DD') : '',
            obj.transaction_number !== undefined ? obj.transaction_number : '',
            obj.amount !== undefined ? obj.amount : '',
            obj.status !== undefined ? trans(`public.${obj.status}`) : '',
        ];
    });

    // Combine headers and rows into a single data array
    const sheetData = [headers, ...rows];

    // Create the XLSX content
    let csvContent = "data:text/xlsx;charset=utf-8,";
    
    sheetData.forEach((rowArray) => {
        const row = rowArray.join("\t"); // Use tabs for column separation
        csvContent += row + "\r\n"; // Add a new line after each row
    });

    // Create a temporary link element
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "export.xlsx");

    // Append the link to the document and trigger the download
    document.body.appendChild(link);
    link.click();

    // Clean up by removing the link
    document.body.removeChild(link);
};

const getStatusColor = (status) => {
  switch(status.toLowerCase()) {
    case 'successful':
      return '#22C55E'; // Green
    case 'processing':
      return '#0EA5E9'; // Blue
    case 'failed':
      return '#DC2626'; // Red
    case 'rejected':
      return '#DC2626'; // Red
    default:
      return 'transparent'; // Default or undefined status
  }
};

const data = ref({});
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
        <div>
            <DataTable
                :value="withdrawals"
                removableSort
                scrollable
                scrollHeight="400px"
                tableStyle="md:min-width: 50rem"
                ref="dt"
                selectionMode="single"
                :loading="loading"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col items-center gap-4 mb-4 md:mb-8">
                        <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:justify-between">
                            <Select 
                                v-model="selectedMonth" 
                                :options="months" 
                                :placeholder="$t('public.month_placeholder')"
                                class="w-full md:w-60 font-normal truncate" scroll-height="236px" 
                            >
                            <template #option="{ option }">
                                <span class="text-sm">
                                    <template v-if="option === 'select_all'">
                                        {{ $t('public.select_all') }}
                                    </template>
                                    <template v-else-if="option.startsWith('last_')">
                                        {{ $t(`public.${option}`) }}
                                    </template>
                                    <template v-else>
                                        {{ $t(`public.${option.split(' ')[1]}`) }} {{ option.split(' ')[2] }}
                                    </template>
                                </span>
                            </template>
                            <template #value>
                                <span v-if="selectedMonth">
                                    <template v-if="selectedMonth === 'select_all'">
                                        {{ $t('public.select_all') }}
                                    </template>
                                    <template v-else-if="selectedMonth.startsWith('last_')">
                                        {{ $t(`public.${selectedMonth}`) }}
                                    </template>
                                    <template v-else>
                                        {{ $t(`public.${dayjs(selectedMonth).format('MMMM')}`) }} {{ dayjs(selectedMonth).format('YYYY') }}
                                    </template>
                                </span>
                                <span v-else>
                                    {{ $t('public.month_placeholder') }}
                                </span>
                            </template>
                            </Select>
                            <Button
                                variant="primary-outlined"
                                @click="withdrawals?.length > 0 ? exportXLSX($event) : null" 
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
                    <Column field="created_at" :header="$t('public.date')" sortable  class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column field="transaction_number" :header="$t('public.id')" sortable  class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ slotProps.data.transaction_number }}
                        </template>
                    </Column>
                    <Column field="amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable  class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ formatAmount(slotProps.data.amount) }}
                        </template>
                    </Column>
                    <Column field="status" :header="$t('public.status')" sortable  class="hidden md:table-cell">
                        <template #body="slotProps">
                            <StatusBadge :variant="slotProps.data.status">{{ $t(`public.${slotProps.data.status}`) }}</StatusBadge>
                        </template>
                    </Column>
                    <ColumnGroup type="footer">
                        <Row>
                            <Column class="hidden md:table-cell" :footer="$t('public.total_approved') + ' ($) :'" :colspan="3" footerStyle="text-align:right" />
                            <Column class="hidden md:table-cell" :footer="formatAmount(totalWithdrawalAmount ? totalWithdrawalAmount : 0)" />
                        </Row>
                    </ColumnGroup>
                    <Column class="md:hidden">
                        <template #body="slotProps">
                            <div class="flex items-center justify-between">
                                <div class="flex flex-col items-start">
                                    <div class=" font-semibold">
                                        {{ slotProps.data.transaction_number }}
                                    </div>
                                    <div class="flex gap-1 items-center text-gray-500 text-xs">
                                        <div>{{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}</div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end">
                                    <div class=" font-semibold text-right">
                                        $&nbsp;{{ formatAmount(slotProps.data.amount) }}
                                    </div>
                                    <div class="text-xs text-right" :style="{ color: getStatusColor(slotProps.data.status) }">
                                        {{ $t(`public.${slotProps.data.status}`) }}
                                    </div>
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
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.wallet_name }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.receiving_address') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.wallet_address }}</span>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.remarks') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.remarks ?? '-' }}</span>
                </div>
            </div>
        </div>
    </Dialog>
</template>