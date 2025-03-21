<script setup>
import InputText from 'primevue/inputtext';
import Button from '@/Components/Button.vue';
import { ref, watch } from "vue";
import Dialog from 'primevue/dialog';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import {FilterMatchMode} from "@primevue/core/api";
import { transactionFormat } from '@/Composables/index.js';
import Empty from '@/Components/Empty.vue';
import Loader from "@/Components/Loader.vue";
import {IconSearch, IconX, IconDownload} from '@tabler/icons-vue';
import Select from "primevue/select";
import { trans, wTrans } from "laravel-vue-i18n";
import dayjs from 'dayjs'

const { formatDate, formatDateTime, formatAmount } = transactionFormat();

const visible = ref(false);
const rebateDetails = ref();
const dt = ref();
const loading = ref(false);
const expandedRows = ref({});
const filteredValue = ref();

const months = ref([]);
const selectedMonth = ref('');

const getCurrentMonthYear = () => {
    const date = new Date();
    return `01 ${dayjs(date).format('MMMM YYYY')}`;
};

// Fetch settlement months from API
const getTransactionMonths = async () => {
    try {
        const response = await axios.get('/getTransactionMonths');
        months.value = response.data.months;

        if (months.value.length) {
            selectedMonth.value = getCurrentMonthYear();
        }
    } catch (error) {
        console.error('Error trade months:', error);
    }
};

getTransactionMonths()

const getResults = async (selectedMonth = '') => {
    loading.value = true;
    try {
        let response;
        let url = `/report/getRebateDetails`;

        if (selectedMonth) {
            let formattedMonth = selectedMonth;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            url += `?selectedMonth=${formattedMonth}`;
        }

        response = await axios.get(url);
        rebateDetails.value = response.data.rebateDetails;

    } catch (error) {
        console.error('Error fetching rebate listing data:', error);
    } finally {
        loading.value = false;
    }
};

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = filteredValue.value;

    // Specify the headers
    const headers = [
        trans('public.name'),
        trans('public.account'),
        trans('public.volume') + ' (Ł)',
        trans('public.rebate') + ' ($)',
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        return [
            obj.name !== undefined ? obj.name : '',
            obj.meta_login !== undefined ? obj.meta_login : '',
            obj.volume !== undefined ? obj.volume : '',
            obj.rebate !== undefined ? obj.rebate : '',
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

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
};

const emit = defineEmits(['update-month']);

watch(selectedMonth, (newMonth) => {
    getResults(newMonth);
    emit('update-month', newMonth);
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

// dialog
const data = ref({});
const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};

</script>

<template>
    <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
        <div class="flex flex-col md:flex-row gap-3 items-center self-stretch">
            <Select 
                v-model="selectedMonth" 
                :options="months" 
                :placeholder="$t('public.month_placeholder')"
                class="w-full md:w-[272px] font-normal truncate" scroll-height="236px" 
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
            <div class="w-full flex justify-end gap-5">
                <div class="relative w-full md:w-60">
                    <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
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
                <Button
                    variant="primary-outlined"
                    @click="filteredValue?.length > 0 ? exportXLSX($event) : null" 
                    class="w-full md:w-auto"
                >
                    <IconDownload size="20" stroke-width="1.25" />
                    {{ $t('public.export') }}
                </Button>
            </div>
        </div>

        <DataTable
            v-model:filters="filters"
            :value="rebateDetails"
            :paginator="rebateDetails?.length > 0"
            removableSort
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50, 100]"
            tableStyle="md:min-width: 50rem"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
            :globalFilterFields="['name','email']"
            ref="dt"
            selectionMode="single"
            @filter="handleFilter"
            @row-click="(event) => openDialog(event.data)"
            :loading="loading"
            >
            <template #empty><Empty :title="$t('public.empty_report_title')" :message="$t('public.empty_report_message')"/></template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loader />
                    <span class="text-sm text-gray-700">{{ $t('public.loading_rebate_record_caption') }}</span>
                </div>
            </template>
            <template v-if="rebateDetails?.length > 0">
                <Column
                    field="name"
                    sortable
                    :header="$t('public.name')"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        <div class="flex flex-col items-start">
                            <div class="font-medium">
                                {{ slotProps.data.name }}
                            </div>
                            <div class="text-gray-500 text-xs">
                                {{ slotProps.data.email }}
                            </div>
                        </div>
                    </template>
                </Column>
                <Column
                    field="meta_login"
                    :header="`${$t('public.account')}`"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        {{ slotProps.data.meta_login }}
                    </template>
                </Column>
                <Column
                    field="volume"
                    sortable
                    :header="`${$t('public.volume')}&nbsp;(Ł)`"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        {{ formatAmount(slotProps.data.volume) }}
                    </template>
                </Column>
                <Column
                    field="rebate"
                    sortable
                    :header="`${$t('public.rebate')}&nbsp;($)`"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        {{ formatAmount(slotProps.data.rebate) }}
                    </template>
                </Column>
                <Column class="md:hidden">
                    <template #body="slotProps">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col items-start">
                                <div class="text-sm font-semibold">
                                    {{ slotProps.data.name }}
                                </div>
                                <div class="text-gray-500 text-xs">
                                    {{ `${slotProps.data.meta_login}&nbsp;|&nbsp;${formatAmount(slotProps.data.volume)}&nbsp;Ł` }}
                                </div>
                            </div>
                            <div class="overflow-hidden text-right text-ellipsis font-semibold">
                                $&nbsp;{{ formatAmount(slotProps.data.rebate) }}
                            </div>
                        </div>
                    </template>
                </Column>
            </template>
        </DataTable>
    </div>

    <Dialog v-model:visible="visible" modal :header="$t('public.rebate_details')" class="dialog-xs md:dialog-md">
        <div class="flex flex-col justify-center items-start pb-4 gap-3 self-stretch border-b border-gray-200 md:flex-row md:pt-4 md:justify-between">
            <!-- below md -->
            <span class="md:hidden self-stretch text-gray-950 text-xl font-semibold">$&nbsp;{{ formatAmount(data.rebate) }}</span>
            <div class="flex flex-col items-start max-w-60 md:max-w-[300px]">
                <span class="self-stretch overflow-hidden text-gray-950 text-ellipsis text-sm font-medium">{{ data.name }}</span>
                <span class="self-stretch overflow-hidden text-gray-500 text-ellipsis text-xs">{{ data.email }}</span>
            </div>
            <!-- above md -->
            <span class="hidden md:block w-[180px] text-gray-950 text-right text-xl font-semibold">$&nbsp;{{ formatAmount(data.rebate) }}</span>
        </div>

        <div class="flex flex-col justify-center items-center py-4 gap-3 self-stretch border-b border-gray-200 md:border-none">
            <div class="min-w-[100px] flex gap-1 flex-grow items-center self-stretch">
                <span class="self-stretch text-gray-500 text-xs font-medium w-[88px] md:w-[140px]">{{ $t('public.date') }}</span>
                <span class="self-stretch text-gray-950 text-sm font-medium flex-grow">{{ selectedMonth === 'select_all' ? $t('public.all') : `${$t(`public.${dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM')}`)} ${dayjs(selectedMonth, 'DD MMMM YYYY').format('YYYY')}` }}</span>
            </div>
            <div class="min-w-[100px] flex gap-1 flex-grow items-center self-stretch">
                <span class="self-stretch text-gray-500 text-xs font-medium w-[88px] md:w-[140px]">{{ $t('public.account') }}</span>
                <span class="self-stretch text-gray-950 text-sm font-medium flex-grow">{{ data.meta_login }}</span>
            </div>
            <div class="min-w-[100px] flex gap-1 flex-grow items-center self-stretch">
                <span class="self-stretch text-gray-500 text-xs font-medium w-[88px] md:w-[140px]">{{ $t('public.total_trade_volume') }}</span>
                <span class="self-stretch text-gray-950 text-sm font-medium flex-grow">{{ formatAmount(data.volume) }}&nbsp;Ł</span>
            </div>
        </div>

        <div class="flex flex-col items-start pt-2 self-stretch md:pt-0">
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="data.summary"
                dataKey="execute_at"
                removableSort
                :pt="{
                    column: {
                        headercell: ({ context, props }) => ({
                            class: [
                                'font-semibold',
                                'text-xs',
                                'uppercase',
                                'box-border',

                                // Position
                                { 'sticky z-20 border-b': props.frozen || props.frozen === '' },

                                { relative: context.resizable },

                                // Shape
                                { 'first:border-l border-y border-r': context?.showGridlines },
                                'border-0 border-b border-solid',

                                // Spacing
                                context?.size === 'small' ? 'py-[0.375rem] px-2' : context?.size === 'large' ? 'py-[0.9375rem] px-5' : 'p-3',

                                // Color
                                (props.sortable === '' || props.sortable) && context.sorted ? 'bg-primary-100 text-primary-500' : 'bg-gray-100 text-gray-950',
                                'border-gray-200 ',

                                // States
                                { 'hover:bg-gray-100': (props.sortable === '' || props.sortable) && !context?.sorted },
                                'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-primary-500 dark:focus-visible:ring-primary-400',

                                // Transition
                                { 'transition duration-200': props.sortable === '' || props.sortable },

                                // Misc
                                { 'cursor-pointer': props.sortable === '' || props.sortable },
                                {
                                    'overflow-hidden space-nowrap border-y bg-clip-padding': context.resizable // Resizable
                                },

                                'hidden md:table-cell',
                            ]
                        }),
                        bodycell: ({ props, context, state, parent }) => ({
                            class: [
                                // Font
                                'text-sm font-semibold md:font-normal',

                                // Alignment
                                'text-left',

                                // Spacing
                                { 'py-2 px-3': context?.size !== 'large' && context?.size !== 'small' && !state['d_editing'] },

                                // Border
                                'border-0 border-b border-solid border-gray-200'
                            ]
                        }),
                    },
                }"
            >
                <!-- Row Expansion Column -->
                <Column expander class="w-9 md:w-20 text-gray-500" />

                <!-- Summary Columns -->
                <Column sortable field="execute_at" header="Date" />
                <Column field="volume" :header="`${$t('public.total_volume')}&nbsp;(Ł)`" class="text-left hidden md:table-cell">
                    <template #body="slotProps">
                        {{ formatAmount(slotProps.data.volume) }}
                    </template>
                </Column>
                <Column field="rebate" :header="`${$t('public.total_rebate')}&nbsp;($)`" class="text-left hidden md:table-cell">
                    <template #body="slotProps">
                        {{ formatAmount(slotProps.data.rebate) }}
                    </template>
                </Column>
                <Column field="rebate" header="`${$t('public.total_rebate')}&nbsp;($)`"  class="text-right md:hidden">
                    <template #body="slotProps">
                        $&nbsp;{{ formatAmount(slotProps.data.rebate) }}
                    </template>
                </Column>

                <!-- Row Expansion Content -->
                <template #expansion="slotProps">
                <!-- Display only details for each summary entry -->
                    <DataTable 
                        :value="slotProps.data.details" 
                        class="pl-9 md:pl-20"
                        unstyled
                        :pt="{
                            column: {
                                headercell: ({ context, props }) => ({
                                    class: [
                                        'font-semibold',
                                        'text-xs',
                                        'uppercase',
                                        'box-border',

                                        // Position
                                        { 'sticky z-20 border-b': props.frozen || props.frozen === '' },

                                        { relative: context.resizable },

                                        // Shape
                                        { 'first:border-l border-y border-r': context?.showGridlines },
                                        'border-0 border-b border-solid',

                                        // Spacing
                                        context?.size === 'small' ? 'py-[0.375rem] px-2' : context?.size === 'large' ? 'py-[0.9375rem] px-5' : 'p-3',

                                        // Color
                                        (props.sortable === '' || props.sortable) && context.sorted ? 'bg-primary-50 text-primary-500' : 'bg-white text-gray-950',
                                        'border-gray-200 ',

                                        // States
                                        { 'hover:bg-gray-100': (props.sortable === '' || props.sortable) && !context?.sorted },
                                        'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-primary-500 dark:focus-visible:ring-primary-400',

                                        // Transition
                                        { 'transition duration-200': props.sortable === '' || props.sortable },

                                        // Misc
                                        { 'cursor-pointer': props.sortable === '' || props.sortable },
                                        {
                                            'overflow-hidden space-nowrap border-y bg-clip-padding': context.resizable // Resizable
                                        },

                                        'hidden md:table-cell',
                                    ]
                                }),
                                bodycell: ({ props, context, state, parent }) => ({
                                    class: [
                                        'flex justify-between items-center md:justify-normal md:items-start',

                                        // Spacing
                                        { 'py-1 md:py-2 px-3': context?.size !== 'large' && context?.size !== 'small' && !state['d_editing'] },

                                        // Border
                                        { 'border-0 border-b border-solid border-gray-200': parent.props.rowIndex != slotProps.data.details.length - 1 }
                                    ]
                                }),
                            },
                        }"
                    >
                        <Column field="name" :header="$t('public.product')" class="text-sm text-left hidden md:table-cell">
                            <template #body="slotProps">
                                {{ $t('public.' + slotProps.data.name) }}
                            </template>
                        </Column>
                        <Column field="volume" :header="`${$t('public.volume')}&nbsp;(Ł)`" class="text-sm text-left hidden md:table-cell">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.volume) }}
                            </template>
                        </Column>
                        <Column field="net_rebate" :header="`${$t('public.rebate')}&nbsp;/&nbsp;Ł&nbsp;($)`" class="text-sm text-left hidden md:table-cell">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.net_rebate) }}
                            </template>
                        </Column>
                        <Column field="rebate" :header="`${$t('public.total')}&nbsp;($)`" class="text-sm text-left hidden md:table-cell">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.rebate) }}
                            </template>
                        </Column>
                        <Column field="name" class="md:hidden">
                            <template #body="slotProps">
                                <div class="flex flex-col items-start">
                                    <span class="overflow-hidden text-xs text-gray-950 text-right text-ellipsis font-semibold">{{ $t('public.' + slotProps.data.name) }}</span>
                                    <div class="flex items-center gap-2 self-stretch">
                                        <span class="text-gray-700 text-xs">{{ `${formatAmount(slotProps.data.volume)}&nbsp;Ł` }}</span>
                                        <span class="text-gray-700 text-sm">|</span>
                                        <span class="text-gray-700 text-xs">{{ `$&nbsp;${formatAmount(slotProps.data.net_rebate)}` }}</span>
                                    </div>
                                </div>
                                <span class="w-[100px] overflow-hidden text-sm text-gray-950 text-right text-ellipsis">$&nbsp;{{ formatAmount(slotProps.data.rebate) }}</span>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </DataTable>
        </div>
    </Dialog>
</template>
