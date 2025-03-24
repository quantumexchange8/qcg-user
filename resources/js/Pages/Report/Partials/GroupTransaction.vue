<script setup>
import { ref, h, watch, computed } from "vue";
import TabPanel from 'primevue/tabpanel';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import Deposit from '@/Pages/Report/Partials/Deposit.vue';
import Withdrawal from '@/Pages/Report/Partials/Withdrawal.vue';
import dayjs from "dayjs";
import { trans, wTrans } from "laravel-vue-i18n";
import { transactionFormat } from "@/Composables/index.js";
import {FilterMatchMode} from "@primevue/core/api";
import {IconSearch, IconX, IconDownload} from '@tabler/icons-vue';
import InputText from 'primevue/inputtext';
import Button from '@/Components/Button.vue';
import Select from "primevue/select";

const { formatAmount } = transactionFormat();

const tabs = ref([
    { title: wTrans('deposit'), component: h(Deposit), type: 'deposit' },
    { title: wTrans('withdrawal'), component: h(Withdrawal), type: 'withdrawal' },
]);

// Function to get query parameter value
const getQueryParam = (key) => {
    return new URL(window.location.href).searchParams.get(key);
};

const filteredValue = ref();
const selectedType = ref(getQueryParam('type') || 'deposit');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

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
        console.error('Error transaction months:', error);
    }
};

getTransactionMonths()

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = filteredValue.value;

    // Specify the headers
    const headers = [
        trans('public.date'),
        trans('public.name'),
        trans('public.account'),
        trans('public.amount') + ' ($)',
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        return [
            obj.created_at !== undefined ? dayjs(obj.created_at).format('YYYY/MM/DD') : '',
            obj.name !== undefined ? obj.name : '',
            obj.meta_login !== undefined ? obj.meta_login : '',
            obj.transaction_amount !== undefined ? obj.transaction_amount : '',
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
    name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const handleFilteredValue = (filteredData) => {
    filteredValue.value = filteredData
};

// Watch for URL changes and update active tab
watch(
    () => window.location.search, 
    () => {
        const newTab = getQueryParam('type');
        if (newTab) {
            selectedType.value = newTab;
            activeIndex.value = tabs.value.findIndex(tab => tab.type === newTab);
        }
    },
    { immediate: true }
);

// Update the URL when the tab changes
const updateType = (event) => {
    const selectedTab = tabs.value[event];
    selectedType.value = selectedTab.type;
    history.pushState({}, '', `/report?tab=group_transaction&type=${selectedTab.type}`);
};
</script>
<template>
    <div class="flex flex-col justify-center items-start py-5 px-3 gap-3 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
        <div class="w-full flex flex-col md:flex-row justify-between items-center self-stretch gap-3 md:gap-0">
            <div class="w-full md:w-auto flex items-center">
                <Tabs v-model:value="activeIndex" class="w-full" @update:value="updateType">
                    <TabList>
                        <Tab v-for="(tab, index) in tabs" :key="tab.title" :value="index">
                            {{ $t('public.' + tab.title) }}
                        </Tab>
                    </TabList>
                </Tabs>
            </div>
            <div class="w-full md:w-auto flex flex-col items-center gap-3 md:hidden md:gap-5">
                <div class="relative w-full md:w-60">
                    <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                        <IconSearch size="20" stroke-width="1.25" />
                    </div>
                    <InputText v-model="filters['global'].value" :placeholder="$t('public.search')" class="font-normal pl-12 w-full md:w-60" />
                    <div
                        v-if="filters['global'].value"
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

        <div class="flex flex-col justify-between items-center gap-3 self-stretch md:flex-row">
            <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-2">
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
                <div class="relative w-full hidden md:flex md:w-60">
                    <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400 z-20">
                        <IconSearch size="20" stroke-width="1.25" />
                    </div>
                    <InputText v-model="filters['global'].value" :placeholder="$t('public.search')" class="font-normal pl-12 w-full md:w-60" />
                    <div
                        v-if="filters['global'].value"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer z-10"
                        @click="clearFilterGlobal"
                    >
                        <IconX size="16" />
                    </div>
                </div>
            </div>
            <Button
                variant="primary-outlined"
                @click="filteredValue?.length > 0 ? exportXLSX($event) : null" 
                class="w-full hidden md:flex md:w-auto"
            >
                <IconDownload size="20" stroke-width="1.25" />
                {{ $t('public.export') }}
            </Button>
        </div>

        <Tabs v-model:value="activeIndex" class="w-full">
            <TabPanels>
                <TabPanel v-for="(tab, index) in tabs" :key="index" :value="index">
                    <component :is="tab.component" v-if="activeIndex === index" :filters="filters" :selectedMonth="selectedMonth"  @updateFilteredValue="handleFilteredValue"/>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
</template>
