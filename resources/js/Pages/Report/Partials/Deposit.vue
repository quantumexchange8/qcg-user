<script setup>
import InputText from 'primevue/inputtext';
import Button from '@/Components/Button.vue';
import { ref, onMounted, watch, watchEffect, computed } from "vue";
import {usePage} from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import {FilterMatchMode} from "@primevue/core/api";
import { transactionFormat } from '@/Composables/index.js';
import Empty from '@/Components/Empty.vue';
import Loader from "@/Components/Loader.vue";
import {IconSearch, IconCircleXFilled, IconAdjustments, IconX} from '@tabler/icons-vue';
import DatePicker from 'primevue/datepicker';

const { formatDate, formatDateTime, formatAmount } = transactionFormat();

const props = defineProps({
    selectedType: String,
    selectedDate: Array,
    filters: Object,
});

const selectedType = ref(props.selectedType);
const selectedDate = ref(props.selectedDate);
const transactions = ref();
const groupTotalDeposit = ref(0);
const dt = ref();
const loading = ref(false);

// Watch for changes in selectedType and call getResults when it changes
watch(() => props.selectedType, (newType) => {
    selectedType.value = newType
}, { immediate: true });

watch(() => props.selectedDate, (newDate) => {
    selectedDate.value = newDate
}, { immediate: true });


const getResults = async (selectedDate = []) => {
    loading.value = true;
    try {
        const [startDate, endDate] = selectedDate;
        let url = `/report/getGroupTransaction?type=${selectedType.value}`;

        // Append date range to the URL if it's not null
        if (startDate && endDate) {
            url += `&startDate=${formatDate(startDate)}&endDate=${formatDate(endDate)}`;
        }

        const response = await axios.get(url);
        transactions.value = response.data.transactions;
        groupTotalDeposit.value = response.data.groupTotalDeposit;
    } catch (error) {
        console.error('Error fetching rebate listing data:', error);
    } finally {
        loading.value = false;
    }
};

// Watch for changes in selectedType
watch(() => props.selectedType, (newType) => {
    selectedType.value = newType;
    getResults(selectedDate.value); // Fetch results based on new type and current date range
});

// Watch for changes in selectedDate
watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        if (startDate && endDate) {
            getResults([startDate, endDate]);
        } else if (startDate || endDate) {
            getResults([startDate || endDate, endDate || startDate]);
        } else {
            getResults([]);
        }
    } else if (newDateRange === null) {
        // If newDateRange is null, treat it as an empty range
        getResults([]);
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
});

const emit = defineEmits(['updateDataTable']);

watch([dt], ([data]) => {
    emit('updateDataTable', {
        data
    });
});

onMounted(() => {
    if(selectedDate.value === null){
        getResults([]);
    } else{
        getResults(selectedDate.value);
    }
})

</script>

<template>
    <div class="flex flex-col items-center px-3 py-5 gap-5 self-stretch rounded-2xl bg-white shadow-table md:px-6 md:gap-5">
        <DataTable
            v-model:filters="props.filters"
            :value="transactions"
            :paginator="transactions?.length > 0"
            removableSort
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50, 100]"
            tableStyle="md:min-width: 50rem"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
            :globalFilterFields="['name']"
            selectionMode="single"
            ref="dt"
            :loading="loading"
            >
            <template #header v-if="transactions?.length > 0">
                <div class="md:hidden">
                    <span class="text-sm font-normal text-gray-500">{{ $t('public.total') + ':' }}</span>
                    <span>{{ ' $ ' + formatAmount(groupTotalDeposit ? groupTotalDeposit : 0)}}</span>
                </div>
            </template>
            <template #empty><Empty :title="$t('public.empty_group_transaction_title')" :message="$t('public.empty_group_transaction_deposit_message')"/></template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loader />
                    <span class="text-sm text-gray-700">{{ $t('public.loading_transactions_caption') }}</span>
                </div>
            </template>
            <template v-if="transactions?.length > 0">
                <Column
                    field="created_at"
                    sortable
                    :header="$t('public.date')"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.created_at) }}
                    </template>
                </Column>
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
                    field="transaction_amount"
                    sortable
                    :header="`${$t('public.amount')}`"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        {{ formatAmount(slotProps.data.transaction_amount) }}
                    </template>
                </Column>
                <ColumnGroup type="footer">
                    <Row>
                        <Column class="hidden md:table-cell" :footer="$t('public.total') + ' ($) :'" :colspan="3" footerStyle="text-align:right" />
                        <Column class="hidden md:table-cell" :footer="formatAmount(groupTotalDeposit ? groupTotalDeposit : 0)" />
                    </Row>
                </ColumnGroup>
                
                <Column class="md:hidden">
                    <template #body="slotProps">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col items-start">
                                <div class="text-sm font-semibold">
                                    {{ slotProps.data.name }}
                                </div>
                                <div class="text-gray-500 text-sm">
                                    {{ `${formatDate(slotProps.data.created_at)}&nbsp;|&nbsp;${slotProps.data.meta_login}` }}
                                </div>
                            </div> 
                            <div class="overflow-hidden text-right text-ellipsis font-semibold">
                                $&nbsp;{{ formatAmount(slotProps.data.transaction_amount) }}
                            </div>
                        </div>
                    </template>
                </Column>
            </template>
        </DataTable>
    </div>

</template>
