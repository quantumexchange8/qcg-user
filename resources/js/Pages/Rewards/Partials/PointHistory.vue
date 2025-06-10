<script setup>
import Dialog from "primevue/dialog";
import Button from "@/Components/Button.vue";
// import Tooltip from "@/Components/Tooltip.vue";
import { computed, ref, watch } from "vue";
import { IconX } from "@tabler/icons-vue";
import { transactionFormat } from "@/Composables/index.js";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Select from 'primevue/select';
import dayjs from 'dayjs'
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import StatusBadge from '@/Components/StatusBadge.vue';
import { trans, wTrans } from "laravel-vue-i18n";
import DatePicker from 'primevue/datepicker';

const visible = ref(false);

// const closeDialog = () => {
//     visible.value = false;
// }

const { formatAmount, formatDate } = transactionFormat();

const loading = ref(false);
const dt = ref();
const pointHistories = ref([]);

const today = new Date();
const minDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const maxDate = ref(today);
const selectedDate = ref(null);

const getResults = async (selectedDate = null) => {
    loading.value = true;

    let response;
    let url = `/rewards/viewAllPointHistory`;

    try {
        if (selectedDate) {
            const [startDate, endDate] = selectedDate;
            url += `?startDate=${formatDate(startDate)}&endDate=${formatDate(endDate)}`;
        }

        response = await axios.get(url);

        pointHistories.value = response.data.pointHistory;
        // console.log(pointHistories)
    } catch (error) {
        console.error('Error fetching trade point history:', error);
    } finally {
        loading.value = false;
    }
};

const clearDate = () => {
    selectedDate.value = null;
};

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;

        if (startDate && endDate) {
            getResults([startDate, endDate]);
        } else if (startDate || endDate) {
            getResults([startDate || endDate, endDate || startDate]);
        } else if (!(startDate && endDate)) {
            getResults(null);
        }
    } else if (newDateRange === null) {
        getResults(null);
    }
    else {
        console.warn('Invalid date range format:', newDateRange);
    }
}, { immediate: true });

const getTransactionLabel = (type) => {
    return type === 'redemption' ? trans('public.used') : trans('public.earned');
};

const getSign = (type) => {
    return type === 'redemption' ? '-' : '+';
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'approved': return 'bg-success-500 text-white';
        case 'processing': return 'bg-warning-500 text-white';
        case 'rejected': return 'bg-error-500 text-white';
        default: return 'bg-gray-500 text-white';
    }
};

const getStatusTooltip = (history) => {
    if (history.status === 'processing') return null;
    return `${history.status} on ${formatDate(history.approved_at)}`;
};
</script>

<template>
    <Button
        type="button"
        variant="gray-outlined"
        class="!py-2"
        @click="visible = true"
    >
        {{ $t('public.view_all') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.point_history')"
        class="dialog-xs md:dialog-sm"
    >
        <div>
            <DataTable
                :value="pointHistories"
                removableSort
                :paginator="pointHistories?.length > 0"
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                ref="dt"
                selectionMode="single"
                :loading="loading"
            >
                <template #header>
                    <div class="flex flex-col items-center gap-4 mb-4 ">
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
                                    :placeholder="$t('public.select_date_range')"
                                    class="w-full md:w-60 font-normal"
                                />
                                <div
                                    v-if="selectedDate && selectedDate.length > 0"
                                    class="absolute top-[11px] right-3 flex justify-center items-center text-gray-400 select-none cursor-pointer bg-white w-6 h-6 "
                                    @click="clearDate"
                                >
                                    <IconX size="20" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template #empty><Empty :title="$t('public.empty_point_history_title')" :message="$t('public.empty_point_history_message')" /></template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="pointHistories?.length > 0">
                <!-- <template> -->
                    <Column field="date" :header="$t('public.date')" sortable style="width: 75%">
                        <template #body="slotProps">
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-950 font-medium">{{ getTransactionLabel(slotProps.data.type) }}</span>
                                <div class="flex flex-row items-center gap-2">
                                    <span class="text-xs text-gray-500">{{ formatDate(slotProps.data.date) }}</span>
                                    <span 
                                        v-if="slotProps.data.type === 'redemption'" 
                                        :class="['px-1 py-0.5 text-xxs rounded-sm', getStatusBadgeClass(slotProps.data.status)]"
                                        v-tooltip.top="slotProps.data.status !== 'processing' ? getStatusTooltip(slotProps.data) : null"
                                    >
                                        {{ $t(`public.${slotProps.data.status ?? 'unknown'}`) }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="amount" :header="$t('public.amount')" sortable style="width: 25%">
                        <template #body="slotProps">
                            <span class="text-sm text-gray-950 font-medium">{{ getSign(slotProps.data.type) }}{{ formatAmount(slotProps.data.amount) }} tp</span>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </Dialog>
</template>