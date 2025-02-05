<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { usePage } from "@inertiajs/vue3";
import { IconX, IconSearch, IconDownload, IconFilterOff } from "@tabler/icons-vue";
import { ref, watch, watchEffect } from "vue";
import Loader from "@/Components/Loader.vue";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import Button from '@/Components/Button.vue';
import Select from "primevue/select";
import DatePicker from 'primevue/datepicker';
import { FilterMatchMode } from '@primevue/core/api';
import Empty from "@/Components/Empty.vue";
import { transactionFormat } from "@/Composables/index.js";
import dayjs from "dayjs";
import debounce from "lodash/debounce.js";
import { trans, wTrans } from "laravel-vue-i18n";
import StatusBadge from '@/Components/StatusBadge.vue';
import {DepositIcon, WithdrawalIcon} from '@/Components/Icons/outline.jsx';

const { formatAmount } = transactionFormat();
const props = defineProps({
    totalDeposit: String,
    totalWithdrawal: String,
})

const visible = ref(false);
const loading = ref(false);
const dt = ref(null);
const transactions = ref();

// Get current date
const today = new Date();

// Define minDate and maxDate
const minDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const maxDate = ref(today);

// Reactive variable for selected date range
const selectedDate = ref([minDate.value, maxDate.value]);
const transactionType = ref('');
const status = ref('');
const search = ref('');
const filteredValue = ref();

// Define the transfer type options
const transactionTypeOption = [
    { name: wTrans('public.deposit'), value: 'deposit' },
    { name: wTrans('public.withdrawal'), value: 'withdrawal' }
];

// Define the status options
const statusOption = [
    { name: wTrans('public.successful'), value: 'successful' },
    { name: wTrans('public.processing'), value: 'processing' },
    { name: wTrans('public.failed'), value: 'failed' },
    { name: wTrans('public.rejected'), value: 'rejected' }
];

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

const getResults = async (search = '', type = '', status = '', dateRanges = null) => {
    loading.value = true;

    try {
        const params = new URLSearchParams();

        if (search) {
            params.append('search', search);
        }

        if (type) {
            params.append('type', type);
        }

        if (status) {
            params.append('status', status);
        }

        if (dateRanges) {
            const [startDate, endDate] = dateRanges;
            params.append('startDate', dayjs(startDate).format('YYYY-MM-DD'));
            params.append('endDate', dayjs(endDate).format('YYYY-MM-DD'));
        }

        const response = await axios.get('/transaction/getTransactionHistory', { params });
        transactions.value = response.data.transactions;

    } catch (error) {
        console.error('Error In Fetch Data:', error);
    } finally {
        loading.value = false;
    }

};

getResults(search.value, transactionType.value, status.value, selectedDate.value);

watch(
    [search, transactionType, status, selectedDate],
    debounce(([searchValue, typeValue, statusValue, dateRange]) => {
        if (Array.isArray(dateRange)) {
            const [startDate, endDate] = dateRange;

            if (startDate && endDate) {
                getResults(searchValue, typeValue, statusValue, [startDate, endDate]);
            } else if (startDate || endDate) {
                getResults(searchValue, typeValue, statusValue, [startDate || endDate, endDate || startDate]);
            } else {
                getResults(searchValue, typeValue, statusValue, null);
            }
        } else if (dateRange === null) {
            getResults(searchValue, typeValue, statusValue, null);
        } else {
            console.warn('Invalid date range format:', dateRange);
        }
    }, 300)
);

// watchEffect(() => {
//     if (usePage().props.toast !== null) {
//         getResults();
//     }
// });

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = filteredValue.value;

    // Specify the headers
    const headers = [
        trans('public.date'),
        trans('public.id'),
        trans('public.description'),
        trans('public.sector'),
        trans('public.amount') + ' ($)',
        trans('public.status'),
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        const fromDisplay = obj.category === 'rebate_wallet' || obj.from === 'cash_wallet'
        ? trans('public.' + obj.category)
        : obj.transaction_type === 'deposit'
            ? obj.to_meta_login
            : obj.transaction_type === 'withdrawal' && obj.from_meta_login !== null
                ? obj.from_meta_login
                : trans('public.wallet'); // Default fallback for other cases


        return [
            obj.created_at !== undefined ? dayjs(obj.created_at).format('YYYY/MM/DD') : '',
            obj.transaction_number !== undefined ? obj.transaction_number : '',
            obj.transaction_type !== undefined ? trans(`public.${obj.transaction_type}`) : '',
            fromDisplay,
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


const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    transaction_number: { value: null, matchMode: FilterMatchMode.CONTAINS },
    sector: { value: null, matchMode: FilterMatchMode.CONTAINS },
    transaction_amount: { value: null, matchMode: FilterMatchMode.EQUALS },
    transactionType: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        transaction_number: { value: null, matchMode: FilterMatchMode.CONTAINS },
        sector: { value: null, matchMode: FilterMatchMode.CONTAINS },
        transaction_amount: { value: null, matchMode: FilterMatchMode.EQUALS },
        transactionType: { value: null, matchMode: FilterMatchMode.EQUALS },
        status: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    selectedDate.value = null;
    transactionType.value = null;
    status.value = null;
    filteredValue = null;
};

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
};

const clearDate = () => {
    selectedDate.value = null;
};

// dialog
const data = ref({});
const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};


</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.transaction')">
        <div class="flex flex-col justify-center items-center gap-5">
            <div class="flex flex-col justify-center items-center self-stretch md:flex-row gap-3 md:gap-5">
                <div class="px-6 py-7 w-full flex flex-col gap-4 rounded-lg justify-center items-center self-stretch bg-white shadow-card">
                    <div><DepositIcon /></div>
                    <div class="text-sm font-medium text-gray-700">{{ $t('public.total_deposit') }}</div>
                    <div class="text-xxl font-semibold text-gray-950">{{ `$&nbsp;${formatAmount(props.totalDeposit)}` }}</div>
                </div>
                <div class="px-6 py-7 w-full flex flex-col gap-4 rounded-lg justify-center items-center self-stretch bg-white shadow-card">
                    <div><WithdrawalIcon /></div>
                    <div class="text-sm font-medium text-gray-700">{{ $t('public.total_withdrawal') }}</div>
                    <div class="text-xxl font-semibold text-gray-950">{{ `$&nbsp;${formatAmount(props.totalWithdrawal)}` }}</div>
                </div>
            </div>

            <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <div class="flex flex-col pb-3 gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between md:pb-0">
                    <span class="text-gray-950 font-semibold self-stretch">{{ $t('public.all_transactions') }}</span>
                    <div class="flex flex-col gap-3 items-center self-stretch md:flex-row md:gap-5">
                        <div class="relative w-full md:w-60">
                            <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                                <IconSearch size="20" stroke-width="1.25" />
                            </div>
                            <InputText v-model="filters['global'].value" :placeholder="$t('public.search')" class="font-normal pl-12 w-full md:w-60" />
                            <div
                                v-if="filters['global'].value !== null"
                                class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                @click="clearFilterGlobal"
                            >
                                <IconX size="16" />
                            </div>
                        </div>
                        <Button variant="primary-outlined" @click="filteredValue?.length > 0 ? exportXLSX($event) : null" class="w-full md:w-auto">
                            <IconDownload size="20" stroke-width="1.25" />
                            {{ $t('public.export') }}
                        </Button>
                    </div>
                </div>
                <DataTable
                    v-model:filters="filters"
                    :value="transactions"
                    :paginator="transactions?.length > 0"
                    removableSort
                    :rows="10"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="$t('public.paginator_caption')"
                    :globalFilterFields="['transaction_number', 'sector', 'transaction_amount']"
                    ref="dt"
                    :loading="loading"
                    selectionMode="single"
                    @filter="handleFilter"
                    @row-click="(event) => openDialog(event.data)"
                >
                    <template #header>
                        <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                            <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                                <div class="relative w-full md:w-56 xl:w-60">
                                    <DatePicker 
                                        v-model="selectedDate"
                                        selectionMode="range"
                                        :manualInput="false"
                                        :maxDate="maxDate"
                                        dateFormat="dd/mm/yy"
                                        showIcon
                                        iconDisplay="input"
                                        :placeholder="$t('public.select_date')"
                                        class="font-normal w-full"
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
                                    v-model="transactionType"
                                    :options="transactionTypeOption"
                                    filter
                                    :filterFields="['name']"
                                    optionLabel="name"
                                    optionValue="value"
                                    :placeholder="$t('public.filter_by_type')"
                                    class="w-full md:w-auto xl:w-60 font-normal"
                                    scroll-height="236px"
                                />
                                <Select
                                    v-model="status"
                                    :options="statusOption"
                                    filter
                                    :filterFields="['name']"
                                    optionLabel="name"
                                    optionValue="value"
                                    :placeholder="$t('public.filter_by_status')"
                                    class="w-full md:w-auto xl:w-60 font-normal"
                                    scroll-height="236px"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="error-outlined"
                                size="base"
                                class='w-full md:w-auto'
                                @click="clearFilter"
                            >
                                <IconFilterOff size="20" stroke-width="1.25" />
                                {{ $t('public.clear') }}
                            </Button>
                        </div>
                    </template>
                    <template #empty>
                        <Empty 
                            :title="$t('public.empty_transaction_title')" 
                            :message="$t('public.empty_transaction_message')" 
                        />
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loader />
                            <span class="text-sm text-gray-700">{{ $t('public.loading_transactions_caption') }}</span>
                        </div>
                    </template>
                    <template v-if="transactions?.length > 0">
                        <Column field="created_at" :header="$t('public.date')" sortable class="hidden md:table-cell w-auto">
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm truncate max-w-full">
                                    {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                                </div>
                            </template>
                        </Column>
                        <Column field="transaction_number" :header="$t('public.id')" sortable class="hidden md:table-cell w-auto">
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm">
                                    {{ slotProps.data.transaction_number }}
                                </div>
                            </template>
                        </Column>
                        <Column field="description" sortable :header="$t('public.description')" class="hidden md:table-cell w-auto">
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm">
                                    {{ $t(`public.${slotProps.data.transaction_type}`) }}
                                </div>
                            </template>
                        </Column>
                        <Column field="sector" :header="$t('public.sector')" class="hidden md:table-cell w-auto">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.category === 'rebate_wallet' || slotProps.data.category === 'cash_wallet'">
                                    {{ $t(`public.${slotProps.data.category}`) }}
                                </div>
                                <div v-else-if="slotProps.data.transaction_type === 'deposit'">
                                    {{ slotProps.data.to_meta_login }}
                                </div>
                                <div v-else-if="slotProps.data.transaction_type === 'withdrawal' && slotProps.data.from_meta_login !== null">
                                    {{ slotProps.data.from_meta_login }}
                                </div>
                                <div v-else>
                                    <!-- Optional: Handle unexpected transaction types -->
                                    {{ $t('public.wallet') }}
                                </div>
                            </template>
                        </Column>
                        <Column field="transaction_amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable class="hidden md:table-cell w-auto">
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm">
                                    {{ formatAmount(slotProps.data.amount) }}
                                </div>
                            </template>
                        </Column>
                        <Column field="status" :header="$t('public.status')" class="hidden md:table-cell w-auto">
                            <template #body="slotProps">
                                <div class="flex py-1.5 items-center flex-1">
                                    <StatusBadge :variant="slotProps.data.status">
                                        {{ $t(`public.${slotProps.data.status}`) }}
                                    </StatusBadge>
                                </div>
                            </template>
                        </Column>
                        <Column class="md:hidden">
                            <template #body="slotProps">
                                <div class="flex items-center justify-between">
                                    <div class="flex flex-col items-start">
                                        <div class=" font-semibold">
                                            {{ $t(`public.${slotProps.data.transaction_type}`) }}
                                        </div>
                                        <div class="flex gap-1 items-center text-gray-500 text-xs">
                                            <div>{{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}</div>
                                            <div>|</div>
                                            <div v-if="slotProps.data.category === 'rebate_wallet' || slotProps.data.category === 'cash_wallet'">
                                                {{ $t(`public.${slotProps.data.category}`) }}
                                            </div>
                                            <div v-else-if="slotProps.data.transaction_type === 'deposit'">
                                                {{ slotProps.data.to_meta_login }}
                                            </div>
                                            <div v-else-if="slotProps.data.transaction_type === 'withdrawal' && slotProps.data.from_meta_login !== null">
                                                {{ slotProps.data.from_meta_login }}
                                            </div>
                                            <div v-else>
                                                <!-- Optional: Handle unexpected transaction types -->
                                                {{ $t('public.wallet') }}
                                            </div>
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
        </div>
        
    </AuthenticatedLayout>

    <Dialog v-model:visible="visible" modal :header="$t('public.details')" class="dialog-xs md:dialog-md">
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
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.sector') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">
                        <!-- {{ data.from_meta_login }} {{ data.to_meta_login }} {{ $t(`public.${slotProps.data.category}`) }} -->
                        <div v-if="data.category === 'rebate_wallet' || data.category === 'cash_wallet'">
                            {{ $t(`public.${data.category}`) }}
                        </div>
                        <div v-else-if="data.transaction_type === 'deposit'">
                            {{ data.to_meta_login }}
                        </div>
                        <div v-else-if="data.transaction_type === 'withdrawal' && data.from_meta_login !== null">
                            {{ data.from_meta_login }}
                        </div>
                        <div v-else>
                            <!-- Optional: Handle unexpected transaction types -->
                            {{ $t('public.wallet') }}
                        </div>
                    </span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.description') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ $t(`public.${data.transaction_type}`) }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm" >{{ $t('public.amount') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ `$&nbsp;${formatAmount(data.amount)}` }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.status') }}</span>
                    <div class="flex items-center flex-1">
                        <StatusBadge :variant="data.status">
                            {{ $t(`public.${data.status}`) }}
                        </StatusBadge>
                    </div>
                </div>
            </div>

            <div v-if="data.transaction_type==='deposit'" class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.sent_address') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.from_wallet_address }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.txid') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.txn_hash }}</span>
                </div>
            </div>

            <div v-else class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
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
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.remarks ? data.remarks : '-' }}</span>
                </div>
            </div>
        </div>
    </Dialog>
</template>
