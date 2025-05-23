<script setup>
import {computed, onMounted, ref, watch, watchEffect} from "vue";
import InputText from 'primevue/inputtext';
import Button from '@/Components/Button.vue';
import {usePage} from '@inertiajs/vue3';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import {FilterMatchMode} from "@primevue/core/api";
import Loader from "@/Components/Loader.vue";
import {
    IconSearch,
    IconX,
    IconFilterOff,
    IconDownload,
} from '@tabler/icons-vue';
import { trans, wTrans } from "laravel-vue-i18n";
import {transactionFormat} from "@/Composables/index.js";
import StatusBadge from "@/Components/StatusBadge.vue";
import Select from "primevue/select";
import Empty from "@/Components/Empty.vue";
import dayjs from 'dayjs'

const loading = ref(false);
const dt = ref();
const users = ref();
const filteredValue = ref();
const { formatDate } = transactionFormat();
const paginator_caption = wTrans('public.paginator_caption');

onMounted(() => {
    getResults();
})

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/network/getDownlineListingData');
        users.value = response.data.users;
    } catch (error) {
        console.error('Error get listing:', error);
    } finally {
        loading.value = false;
    }
};

const getFilterData = async () => {
    try {
        const uplineResponse = await axios.get('/network/getFilterData');
        maxLevel.value = uplineResponse.data.maxLevel;
        createLevelOptions();
    } catch (error) {
        console.error('Error filter data:', error);
    }
};

getFilterData();

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = filteredValue.value;

    // Specify the headers
    const headers = [
        trans('public.level'),
        trans('public.name'),
        trans('public.joined_date'),
        trans('public.role'),
        trans('public.upline'),
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        return [
            obj.level !== undefined ? obj.level : '',
            obj.name !== undefined ? obj.name : '',
            obj.joined_date !== undefined ? dayjs(obj.joined_date).format('YYYY/MM/DD') : '',
            obj.role !== undefined ? trans(`public.${obj.role}`) : '',
            obj.upline_name !== undefined ? obj.upline_name : '',
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
    level: { value: null, matchMode: FilterMatchMode.EQUALS },
    role: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const rowClicked = (id_number) => {
    window.open(route('network.viewDownline', id_number), '_self')
}

// overlay panel

const maxLevel = ref(0)
const levels = ref([])
const level = ref(null)
const lvl = computed(() => trans('public.level'));
const roles = [
    { name: wTrans('public.member'), value: 'member' },
    { name: wTrans('public.agent'), value: 'agent' }
];

const createLevelOptions = () => {
    for (let index = 1; index <= maxLevel.value; index++) {
        levels.value.push({
            value: index,
            name: `${lvl.value} ${index}`
        })
    }
}

watch([level], ([newLevel]) => {
    if (level.value !== null) {
        filters.value['level'].value = newLevel
    }
})

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        level: { value: null, matchMode: FilterMatchMode.EQUALS },
        role: { value: null, matchMode: FilterMatchMode.EQUALS },
        status: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    level.value = null;
    filteredValue = null;
};

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

</script>

<template>
    <div class="py-5 px-3 md:p-6 flex flex-col items-center justify-center self-stretch gap-5 md:gap-6 bg-white shadow-card rounded-lg">
        <div class="flex flex-col items-center self-stretch gap-3 md:gap-6">
            <div class="flex flex-col gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between">
                <span class="text-gray-950 font-semibold self-start md:self-center">{{ $t('public.all_users') }}</span>
                <div class="flex flex-col gap-3 items-center self-stretch md:hidden">
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

            <div class="flex flex-col justify-between items-center gap-3 self-stretch md:flex-row">
                <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-2">
                    <Select
                        v-model="level"
                        :options="levels"
                        filter
                        :filterFields="['name']"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.filter_by_level')"
                        class="w-full md:w-auto xl:w-48 font-normal"
                        scroll-height="236px"
                    />
                    <Select
                        v-model="filters['role'].value"
                        :options="roles"
                        filter
                        :filterFields="['name']"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.filter_by_role')"
                        class="w-full md:w-auto xl:w-48 font-normal"
                        scroll-height="236px"
                    />
                    <div class="relative hidden md:flex w-full xl:w-72">
                        <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500 z-20">
                            <IconSearch size="20" stroke-width="1.25" />
                        </div>
                        <InputText v-model="filters['global'].value" :placeholder="$t('public.search')" class="font-normal pl-12 w-full xl:w-72 focus:outline-none" />
                        <div
                            v-if="filters['global'].value !== null"
                            class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer z-10"
                            @click="clearFilterGlobal"
                        >
                            <IconX size="16" />
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row gap-3 md:gap-2 w-full md:w-auto">
                    <Button variant="primary-outlined" @click="filteredValue?.length > 0 ? exportXLSX($event) : null" class="md:w-auto hidden md:flex">
                        <IconDownload size="20" stroke-width="1.25" />
                            {{ $t('public.export') }}
                        </Button>
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
            </div>
        </div>
        <DataTable
            v-model:filters="filters"
            :value="users"
            paginator
            removableSort
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50, 100]"
            tableStyle="min-width: 50rem"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            :currentPageReportTemplate="paginator_caption"
            :globalFilterFields="['name','email','account']"
            ref="dt"
            :loading="loading"
            table-style="min-width:fit-content"
            selectionMode="single"
            @filter="handleFilter"
            @row-click="rowClicked($event.data.id_number)"
        >
            <template #empty>
                <Empty 
                    :title="$t('public.empty_downline_title')" 
                    :message="$t('public.empty_downline_message')" 
                />
            </template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loader />
                    <span class="text-sm text-gray-700">{{ $t('public.loading_users_caption') }}</span>
                </div>
            </template>
            <Column field="level" sortable style="width:10%" class="hidden md:table-cell">
                <template #header>
                    <span>{{ $t('public.level') }}</span>
                </template>
                <template #body="slotProps">
                    <span class="md:hidden">Lvl </span>
                    {{ slotProps.data.level }}
                </template>
            </Column>
            <Column field="name" sortable :header="$t('public.name')"  class="md:w-auto">
                <template #body="slotProps">
                    <div class="flex flex-col items-start">
                        <div class="w-auto truncate font-medium">
                            {{ slotProps.data.name }}
                        </div>
                        <div class="w-auto truncate text-gray-500 text-xs">
                            {{ slotProps.data.email }}
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="joined_date" sortable style="width:20%" class="hidden md:table-cell">
                <template #header>
                    <span>{{ $t('public.joined_date') }}</span>
                </template>
                <template #body="slotProps">
                    {{ formatDate(slotProps.data.joined_date) }}
                </template>
            </Column>
            <Column field="role"  class="w-1/3 md:w-auto">
                <template #header>
                    <span>{{ $t('public.role') }}</span>
                </template>
                <template #body="slotProps">
                    <div class="flex py-1.5 items-center flex-1">
                        <div 
                            :class="{
                                'w-2.5 h-2.5 rounded-full': true, 
                                'bg-orange': slotProps.data.role === 'agent', 
                                'bg-info-500': slotProps.data.role === 'member', 
                                'bg-gray-500': slotProps.data.role !== 'agent' && slotProps.data.role !== 'member'
                            }"
                            :title="$t(`public.${slotProps.data.role}`)">
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="upline" sortable :header="$t('public.upline')" style="width:25%" class="hidden md:table-cell">
                <template #body="slotProps">
                    <div class="flex flex-col items-start">
                        <div class="w-20 font-medium xl:w-36">
                            {{ slotProps.data.upline_name }}
                        </div>
                        <div class="w-20 text-gray-500 text-xs xl:w-36">
                            {{ slotProps.data.upline_email }}
                        </div>
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
