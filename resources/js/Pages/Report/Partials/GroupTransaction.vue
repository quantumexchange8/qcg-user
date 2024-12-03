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
import {IconSearch, IconCircleXFilled, IconX, IconDownload} from '@tabler/icons-vue';
import InputText from 'primevue/inputtext';
import Button from '@/Components/Button.vue';
import DatePicker from 'primevue/datepicker';

const { formatAmount } = transactionFormat();

const tabs = ref([
    { title: wTrans('deposit'), component: h(Deposit), type: 'deposit' },
    { title: wTrans('withdrawal'), component: h(Withdrawal), type: 'withdrawal' },
]);

const filteredValue = ref();
const selectedType = ref('deposit');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

// Get current date
const today = new Date();

// Define minDate as the start of the current month and maxDate as today
const minDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const maxDate = ref(today);

// Reactive variable for selected date range
const selectedDate = ref([minDate.value, maxDate.value]);

// Clear date selection
const clearDate = () => {
    selectedDate.value = null;
};

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

</script>
<template>
    <div class="flex flex-col justify-center items-start py-5 px-3 gap-3 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
        <div class="w-full flex flex-col md:flex-row justify-between items-center self-stretch gap-3 md:gap-0">
            <div class="w-full md:w-auto flex items-center">
                <Tabs v-model:value="activeIndex" class="w-full">
                    <TabList>
                        <Tab v-for="(tab, index) in tabs" :key="tab.title" :value="index">
                            {{ $t('public.' + tab.title) }}
                        </Tab>
                    </TabList>
                </Tabs>
            </div>
            <div class="w-full md:w-auto flex flex-col items-center gap-3 md:flex-row md:gap-5">
                <div class="relative w-full md:w-60">
                    <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                        <IconSearch size="20" stroke-width="1.25" />
                    </div>
                    <InputText v-model="filters['global'].value" :placeholder="$t('public.search')" class="font-normal pl-12 w-full md:w-60" />
                    <div
                        
                        class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                        @click="clearFilterGlobal"
                    >
                        <IconCircleXFilled size="16" />
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

        <div class="relative w-full md:w-[272px]">
            <DatePicker
                v-model="selectedDate"
                selectionMode="range"
                :manualInput="false"
                :maxDate="maxDate"
                dateFormat="dd/mm/yy"
                showIcon
                iconDisplay="input"
                placeholder="dd/mm/yyyy - dd/mm/yyyy"
                class="w-full md:w-[272px]"
            />
            <div
                v-if="selectedDate && selectedDate.length > 0"
                class="absolute top-[11px] right-3 flex justify-center items-center text-gray-400 select-none cursor-pointer bg-white w-6 h-6 "
                @click="clearDate"
            >
                <IconX size="20" />
            </div>
        </div>

        <Tabs v-model:value="activeIndex" class="w-full">
            <TabPanels>
                <TabPanel :key="activeIndex" :value="activeIndex">
                    <component :is="tabs[activeIndex].component" :key="tabs[activeIndex].type" :filters="filters" :selectedType="tabs[activeIndex].type" :selectedDate="selectedDate" v-if="tabs[activeIndex].component" @updateFilteredValue="handleFilteredValue"/>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
</template>
