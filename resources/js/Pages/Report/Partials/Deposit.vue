<script setup>
import { ref, watch } from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import { transactionFormat } from '@/Composables/index.js';
import Empty from '@/Components/Empty.vue';
import Loader from "@/Components/Loader.vue";
import dayjs from "dayjs";

const { formatDate, formatDateTime, formatAmount } = transactionFormat();

const props = defineProps({
    selectedType: String,
    selectedMonths: Array,
    filters: Object,
});

const selectedType = ref(props.selectedType);
const selectedMonths = ref(props.selectedMonths);
const transactions = ref();
const groupTotalDeposit = ref(0);
const dt = ref();
const filteredValue = ref();
const loading = ref(false);

const getResults = async (selectedMonths = []) => {
    loading.value = true;
    try {
        let url = `/report/getGroupTransaction?type=${selectedType.value}`;

        // Append date range to the URL if it's not null
        if (selectedMonths && selectedMonths.length > 0) {
            const selectedMonthString = selectedMonths.map(month => dayjs(month, '01 MMMM YYYY').format('MM/YYYY')).join(',');
            url += `&selectedMonths=${selectedMonthString}`;
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

// Watch for changes in selectedDate
watch(() => props.selectedMonths, (newMonths) => {
    selectedMonths.value = newMonths;
    getResults(newMonths);
});

const emit = defineEmits(['updateFilteredValue']);

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;

    emit('updateFilteredValue', filteredValue.value);
};

watch(transactions, (newTransactions) => {
    if (newTransactions.length === 0) {
        filteredValue.value = [];
        emit('updateFilteredValue', []);
    }
});

</script>

<template>
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
        :globalFilterFields="['name','email']"
        ref="dt"
        @filter="handleFilter"
        :loading="loading"
        >
        <template #header v-if="transactions?.length > 0">
            <div class="md:hidden">
                <span class="text-sm font-normal text-gray-500">{{ $t('public.total') + ':' }}</span>
                <span>{{ ' $ ' + formatAmount(groupTotalDeposit ? groupTotalDeposit : 0)}}</span>
            </div>
        </template>
        <template #empty><Empty :title="$t('public.empty_transaction_title')" :message="$t('public.empty_transaction_message')"/></template>
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
            
            <Column headerClass="hidden" class="md:hidden">
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

</template>
