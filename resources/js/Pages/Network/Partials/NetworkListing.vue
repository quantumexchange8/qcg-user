<script setup>
import {computed, onMounted, ref, watch, watchEffect} from "vue";
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import Button from '@/Components/Button.vue';
import {usePage} from '@inertiajs/vue3';
import OverlayPanel from 'primevue/overlaypanel';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import {FilterMatchMode} from "@primevue/core/api";
import Loader from "@/Components/Loader.vue";
import Dropdown from "primevue/dropdown";
import {
    IconSearch,
    IconCircleXFilled,
    IconFilterOff,
    IconDownload,
} from '@tabler/icons-vue';
import Badge from '@/Components/Badge.vue';
import { trans, wTrans } from "laravel-vue-i18n";
import {transactionFormat} from "@/Composables/index.js";
import StatusBadge from "@/Components/StatusBadge.vue";
import Select from "primevue/select";
import Empty from "@/Components/Empty.vue";

const loading = ref(false);
const dt = ref();
const users = ref();
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
        uplines.value = uplineResponse.data.uplines;
        maxLevel.value = uplineResponse.data.maxLevel;
        createLevelOptions();
    } catch (error) {
        console.error('Error filter data:', error);
    }
};

getFilterData();

const exportCSV = () => {
    dt.value.exportCSV();
};

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    upline_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    level: { value: null, matchMode: FilterMatchMode.EQUALS },
    role: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const rowClicked = (id_number) => {
    window.open(route('network.viewDownline', id_number), '_self')
}

// overlay panel
const op = ref();
const uplines = ref()
const maxLevel = ref(0)
const levels = ref([])
const upline_id = ref(null)
const level = ref(null)
const filterCount = ref(0);
const lvl = trans('public.level');
const roles = [
    { name: wTrans('public.member'), value: 'member' },
    { name: wTrans('public.agent'), value: 'agent' }
];

const toggle = (event) => {
    op.value.toggle(event);
}

const createLevelOptions = () => {
    for (let index = 1; index <= maxLevel.value; index++) {
        levels.value.push({
            value: index,
            name: `${lvl} ${index}`
        })
    }
}

watch([upline_id, level], ([newUplineId, newLevel]) => {
    if (upline_id.value !== null) {
        filters.value['upline_id'].value = newUplineId.value
    }

    if (level.value !== null) {
        filters.value['level'].value = newLevel.value
    }
})

watch(filters, () => {
    // Count active filters
    filterCount.value = Object.values(filters.value).filter(filter => filter.value !== null).length;
}, { deep: true });

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        upline_id: { value: null, matchMode: FilterMatchMode.EQUALS },
        level: { value: null, matchMode: FilterMatchMode.EQUALS },
        role: { value: null, matchMode: FilterMatchMode.EQUALS },
        status: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    upline_id.value = null;
    level.value = null;
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
                <span class="text-gray-950 font-semibold self-center">{{ $t('public.all_downlines') }}</span>
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
                            <IconCircleXFilled size="16" />
                        </div>
                    </div>
                    <Button variant="primary-outlined" @click="exportCSV" class="w-full md:w-auto">
                        <IconDownload size="20" stroke-width="1.25" />
                        {{ $t('public.export') }}
                    </Button>
                </div>
            </div>

            <div class="flex flex-col justify-between items-center gap-3 self-stretch md:flex-row">
                <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                    <Select
                        v-model="level"
                        :options="levels"
                        filter
                        :filterFields="['name']"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.filter_by_level')"
                        class="w-full md:w-60 font-normal"
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
                        class="w-full md:w-60 font-normal"
                        scroll-height="236px"
                    />
                    <Select
                        v-model="upline_id"
                        :options="uplines"
                        filter
                        :filterFields="['name']"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.filter_by_upline')"
                        class="w-full md:w-60 font-normal"
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
            :globalFilterFields="['name']"
            ref="dt"
            :loading="loading"
            table-style="min-width:fit-content"
            selectionMode="single"
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
            <Column field="level" sortable headerClass="hidden md:table-cell" class="w-1/5 md:w-auto">
                <template #header>
                    <span>{{ $t('public.level') }}</span>
                </template>
                <template #body="slotProps">
                    <span class="md:hidden">Lvl </span>
                    {{ slotProps.data.level }}
                </template>
            </Column>
            <Column field="name" sortable :header="$t('public.name')" headerClass="hidden md:table-cell" class="w-auto">
                <template #body="slotProps">
                    <div class="flex flex-col items-start">
                        <div class="w-20 font-medium xl:w-36">
                            {{ slotProps.data.name }}
                        </div>
                        <div class="w-20 text-gray-500 text-xs xl:w-36">
                            {{ slotProps.data.email }}
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="joined_date" sortable style="width: 15%" class="hidden md:table-cell">
                <template #header>
                    <span>{{ $t('public.joined_date') }}</span>
                </template>
                <template #body="slotProps">
                    {{ formatDate(slotProps.data.joined_date) }}
                </template>
            </Column>
            <Column field="role" headerClass="hidden md:table-cell">
                <template #header>
                    <span>{{ $t('public.role') }}</span>
                </template>
                <template #body="slotProps">
                    <div class="flex py-1.5 items-center flex-1">
                        <StatusBadge :variant="slotProps.data.role">
                            {{ $t(`public.${slotProps.data.role}`) }}
                        </StatusBadge>
                    </div>
                </template>
            </Column>
            <Column field="upline" sortable :header="$t('public.upline')" class="hidden md:table-cell w-auto">
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
