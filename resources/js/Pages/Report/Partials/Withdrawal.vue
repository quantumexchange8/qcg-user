<script setup>
import { ref, watch, onMounted } from "vue";
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
    selectedMonth: {
        type: [Array, String], // Allows both Array and String types
        default: () => '', // Default value as an empty array
    },
    filters: Object,
});

const selectedType = ref('withdrawal');
const selectedMonth = ref(props.selectedMonth);
const transactions = ref();
const groupTotalWithdrawal = ref(0);
const dt = ref();
const filteredValue = ref();
const loading = ref(false);

const getResults = async (selectedMonth = '') => {
    loading.value = true;
    try {
        let url = `/report/getGroupTransaction?type=${selectedType.value}`;

        if (selectedMonth) {
            let formattedMonth = selectedMonth;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            url += `&selectedMonth=${formattedMonth}`;
        }

        const response = await axios.get(url);
        transactions.value = response.data.transactions;
        groupTotalWithdrawal.value = response.data.groupTotalWithdrawal;
    } catch (error) {
        console.error('Error fetching rebate listing data:', error);
    } finally {
        loading.value = false;
    }
};

// Watch for changes in selectedDate
watch(() => props.selectedMonth, (newMonth) => {
    selectedMonth.value = newMonth;
    getResults(newMonth);
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

onMounted(() => {
    if (props.selectedMonth) {
        getResults(props.selectedMonth);
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
                <span>{{ ' $ ' + formatAmount(groupTotalWithdrawal ? groupTotalWithdrawal : 0)}}</span>
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
                    <span v-if="slotProps.data.meta_login === 'rebate'">{{ $t('public.rebate') }}</span>
                    <span v-else-if="slotProps.data.meta_login === 'bonus'">{{ $t('public.bonus') }}</span>
                    <span v-else>{{ slotProps.data.meta_login }}</span>
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
                    <Column class="hidden md:table-cell" :footer="formatAmount(groupTotalWithdrawal ? groupTotalWithdrawal : 0)" />
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
