<script setup>
import { transactionFormat } from "@/Composables/index.js";
import { ref, computed, watch, onMounted } from "vue";
import Select from 'primevue/select';
import Chart from 'primevue/chart';
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff } from "@tabler/icons-vue";
import Loader from "@/Components/Loader.vue";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import ColumnGroup from "primevue/columngroup";
import Row from "primevue/row";
import Button from '@/Components/Button.vue';
import DatePicker from 'primevue/datepicker';
import { FilterMatchMode } from '@primevue/core/api';
import Empty from "@/Components/Empty.vue";
import dayjs from "dayjs";
import debounce from "lodash/debounce.js";
import { trans, wTrans } from "laravel-vue-i18n";

const { formatAmount, formatDate } = transactionFormat();
// Chart labels (static, for months)
const labels = ['Forex', 'Stocks', 'Indices', 'Commodities', 'Cryptocurrency'];

// To hold the chart data from the API
const chartData = ref(new Array(5).fill(0)); // Initialize with zeros for each month

// Chart options (optional for additional customization)
const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: {
            position: 'bottom',
            labels:{
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20
            }
        },
        title: {
            display: false, // Disable the title
        }
    }
});

const loading = ref(false);
// Get current date
const today = new Date();

// Define minDate and maxDate
const minDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const maxDate = ref(today);

// Reactive variable for selected date range
const selectedDate = ref([minDate.value, maxDate.value]);

// Function to fetch results
// const getResults = async () => {
//     loading.value = true;

//     try {
//         let url = `/report/getRebateBreakdown`;

//         const response = await axios.get(url);

//     } catch (error) {
//         console.error('Error fetching data:', error);
//     } finally {
//         loading.value = false;
//     }
// };

// // Call getResults initially with the current year
// getResults();

// Combine the static labels with the dynamic data from the API
const formattedChartData = computed(() => {
    return {
        labels,
        datasets: [
            {
                label: 'Rebate Distribution',
                backgroundColor: ['#204724', '#2A6B2D', '#42A547', '#9BDA9E', '#E2F6E3'],
                data: chartData.value // This should always have 5 values, filled with zeros or real data
            }
        ]
    };
});

</script>

<template>
    <div class="flex flex-col items-center gap-5 self-stretch">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 xl:h-[394px] justify-center items-center self-stretch">
            <div class="flex flex-col px-8 py-6 items-center gap-7 flex-1 self-stretch rounded-lg bg-white shadow-card">
                <span class="text-gray-950 text-md font-bold">
                    {{ $t('public.rebate_percentage_breakdown') }}
                </span>
                <div class="">
                    <Chart 
                        type="doughnut" 
                        :data="formattedChartData" 
                        :options="chartOptions"
                        style="width: 100%;"
                    />
                </div>
            </div>
            <div class="flex flex-col px-8 py-6 items-center gap-3 flex-1 rounded-lg bg-white shadow-card">
                <div class="flex items-center gap-5 self-stretch">
                    <div class="flex flex-col py-3 items-center gap-2 flex-1">
                        <span class="text-gray-500 text-center">{{ $t('public.total_trade_volume') }} (Ł)</span>
                        <span class="text-gray-950 text-xxl font-semibold">0.00</span>
                    </div>
                    <div>
                        
                    </div>
                    <div class="flex flex-col py-3 items-center gap-2 flex-1">
                        <span class="text-gray-500 text-center">{{ $t('public.total_rebate_earned') }} ($)</span>
                        <span class="text-gray-950 text-xxl font-semibold">0.00</span>
                    </div>
                </div>
                <div class="flex flex-col justify-center items-center self-stretch">
                    <div class="flex py-3 items-center gap-5 self-stretch">
                        <span class="flex-1 text-gray-950 text-md font-semibold">{{ $t('public.forex') }}</span>
                        <span class="flex-1 text-gray-950 text-md text-right">0.00 Ł</span>
                        <span class="flex-1 text-gray-950 text-md text-right">$ 0.00</span>
                    </div>
                    <div class="flex py-3 items-center gap-5 self-stretch">
                        <span class="flex-1 text-gray-950 text-md font-semibold">{{ $t('public.stocks') }}</span>
                        <span class="flex-1 text-gray-950 text-md text-right">0.00 Ł</span>
                        <span class="flex-1 text-gray-950 text-md text-right">$ 0.00</span>
                    </div>
                    <div class="flex py-3 items-center gap-5 self-stretch">
                        <span class="flex-1 text-gray-950 text-md font-semibold">{{ $t('public.indices') }}</span>
                        <span class="flex-1 text-gray-950 text-md text-right">0.00 Ł</span>
                        <span class="flex-1 text-gray-950 text-md text-right">$ 0.00</span>
                    </div>
                    <div class="flex py-3 items-center gap-5 self-stretch">
                        <span class="flex-1 text-gray-950 text-md font-semibold">{{ $t('public.commodities') }}</span>
                        <span class="flex-1 text-gray-950 text-md text-right">0.00 Ł</span>
                        <span class="flex-1 text-gray-950 text-md text-right">$ 0.00</span>
                    </div>
                    <div class="flex py-3 items-center gap-5 self-stretch">
                        <span class="flex-1 text-gray-950 text-md font-semibold">{{ $t('public.cryptocurrency') }}</span>
                        <span class="flex-1 text-gray-950 text-md text-right">0.00 Ł</span>
                        <span class="flex-1 text-gray-950 text-md text-right">$ 0.00</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="flex flex-col pb-3 gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between md:pb-0">
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
                <div class="flex flex-col gap-3 items-center self-stretch md:flex-row md:gap-5">
                    <div class="relative w-full md:w-60">
                        <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                            <IconSearch size="20" stroke-width="1.25" />
                        </div>
                        <InputText          :placeholder="$t('public.search')" class="font-normal pl-12 w-full md:w-60" />
                        <div
                            
                            class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                            @click="clearFilterGlobal"
                        >
                            <IconCircleXFilled size="16" />
                        </div>
                    </div>
                    <Button variant="primary-outlined" @click="exportCSV" class="w-full md:w-auto">
                        <IconDownload size="20" stroke-width="1.25" />
                        {{ $t('public.export') }}
                    </Button>
                </div>
            </div>
            <DataTable
                v-model:filters="filters"
                :value="transactions"
                :paginator="transactions?.length > 0 && filteredValueCount > 0"
                removableSort
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :globalFilterFields="['name', 'email', 'account']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @filter="handleFilter"
                @row-click="(event) => openDialog(event.data)"
            >
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
                <template v-if="transactions?.length > 0 && filteredValueCount > 0">
                    <Column field="name" :header="$t('public.name')" sortable class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data.name }}
                            </div>
                        </template>
                    </Column>
                    <Column field="account" :header="$t('public.account')" class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data.account }}
                            </div>
                        </template>
                    </Column>
                    <Column field="volume" :header="`${$t('public.volume')}&nbsp;(Ł)`" sortable class="w-1/2 md:w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ formatAmount(slotProps.data.volume) }}
                            </div>
                        </template>
                    </Column>
                    <Column field="rebate" :header="`${$t('public.rebate')}&nbsp;($)`" sortable class="w-1/2 md:w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ formatAmount(slotProps.data.rebate) }}
                            </div>
                        </template>
                    </Column>

                </template>
            </DataTable>
        </div>
    </div>
    
</template>