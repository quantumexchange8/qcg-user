<script setup>
import Dialog from "primevue/dialog";
import Button from "@/Components/Button.vue";
// import Tooltip from "@/Components/Tooltip.vue";
import { computed, ref, watch } from "vue";
import { IconX, IconReport, IconDownload } from "@tabler/icons-vue";
import { transactionFormat } from "@/Composables/index.js";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Select from 'primevue/select';
import dayjs from 'dayjs'
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import { trans, wTrans } from "laravel-vue-i18n";

const visible = ref(false);
const visible2 = ref(false);

const closeDialog = () => {
    visible.value = false;
}

const { formatAmount, formatDate } = transactionFormat();

const loading = ref(false);
const descriptions = [
    { name: wTrans('public.all'), value: 'all' },
    { name: wTrans('public.rebate_payout'), value: 'rebate_payout' },
    { name: wTrans('public.apply_rebate'), value: 'apply_rebate' },
    { name: wTrans('public.transfer'), value: 'transfer' },
    { name: wTrans('public.withdrawal'), value: 'withdrawal' }
];
const selectedDescription = ref(descriptions[0].value);
const dt = ref();
const rebate = ref();

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

const getResults = async (description = '', selectedMonth = '') => {
    loading.value = true;
    try {
        const params = new URLSearchParams();

        if (description) {
            params.append('description', description);
        }

        if (selectedMonth) {
            let formattedMonth = selectedMonth;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            params.append('selectedMonth', formattedMonth);
        }

        // console.log(description)
        const response = await axios.get('/dashboard/getRebateTransactions', { params });
        // console.log('Params:', params.toString());
        // console.log(response.data);
        rebate.value = response.data.transactions;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

watch(selectedMonth, (newMonth) => {
    getResults(selectedDescription.value, newMonth);
});

watch(selectedDescription, (descriptionValue) => {
    getResults(descriptionValue, selectedMonth.value);
});


const getAmountTextColor = (transactionType) => {
    if (['rebate_payout', 'rebate_in'].includes(transactionType)) {
        return 'text-success-600'; 
    } else {
        return 'text-error-600'; 
    }
};

// dialog
const data = ref({});
const openDialog = (rowData) => {
    visible2.value = true;
    data.value = rowData;
};

</script>

<template>
    <Button
        variant="gray-outlined"
        type="button"
        size="sm"
        iconOnly
        pill
        @click="visible = true"
    >
        <IconReport size="16" stroke-width="1.5" />
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.rebate_history')"
        class="dialog-xs md:dialog-md"
    >
        <div>
            <DataTable
                :value="rebate"
                removableSort
                :paginator="rebate?.length > 0"
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                ref="dt"
                selectionMode="single"
                :loading="loading"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col gap-4 items-center mb-4 md:mb-8">
                        <div class="flex flex-col gap-5 items-center md:flex-row self-stretch">
                            <Select 
                                v-model="selectedMonth" 
                                :options="months" 
                                :placeholder="$t('public.month_placeholder')"
                                class="w-full font-normal md:w-60 truncate" scroll-height="236px" 
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
                            <Select 
                                v-model="selectedDescription"
                                :options="descriptions"
                                optionLabel="name"
                                optionValue="value"
                                class="w-full font-normal md:w-60"
                            />
                        </div>
                    </div>
                </template>
                <template #empty><Empty :title="$t('public.empty_rebate_history_title')" :message="$t('public.empty_rebate_history_message')" /></template>
                <template #loading>
                    <div class="flex flex-col justify-center gap-2 items-center">
                        <Loader />
                        <span class="text-gray-700 text-sm">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="rebate?.length > 0">
                <!-- <template> -->
                    <Column field="created_at" :header="$t('public.date')" sortable style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column field="transaction_number" :header="$t('public.id')" sortable style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ slotProps.data.transaction_number }}
                        </template>
                    </Column>
                    <Column field="description" :header="$t('public.description')" style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <template v-if="slotProps.data.transaction_type === 'transfer_to_account'">
                                {{ $t('public.to') }} {{ slotProps.data.to_meta_login }}
                            </template>
                            <template v-else>
                                {{ $t(`public.${slotProps.data.transaction_type}`) }}
                            </template>
                        </template>
                    </Column>
                    <Column field="amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <span :class="getAmountTextColor(slotProps.data.transaction_type)">
                                {{ formatAmount(slotProps.data.amount) }}
                            </span>
                        </template>
                    </Column>
                    <!-- <Column field="status" :header="$t('public.status')" style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="flex flex-1 items-center py-1.5">
                                <StatusBadge :variant="slotProps.data.status">
                                    {{ $t(`public.${slotProps.data.status}`) }}
                                </StatusBadge>
                            </div>
                        </template>
                    </Column> -->
                    <Column class="md:hidden">
                        <template #body="slotProps">
                            <div class="flex justify-between items-center">
                                <div class="flex flex-col items-start">
                                    <div class="text-sm font-semibold">
                                        {{ $t(`public.${slotProps.data.transaction_type}`) }}
                                    </div>
                                    <div class="text-gray-500 text-xs">
                                        {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                                    </div>
                                </div>
                                <div class="text-ellipsis text-right font-semibold overflow-hidden" :class="getAmountTextColor(slotProps.data.transaction_type)">
                                    $&nbsp;{{ formatAmount(slotProps.data.amount) }}
                                </div>
                            </div>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </Dialog>

    <Dialog v-model:visible="visible2" modal :header="$t('public.details')" class="dialog-xs md:dialog-md">
        <div class="flex flex-col justify-center gap-3 items-center md:pt-6 pt-4 self-stretch">
            <div class="flex flex-col bg-gray-50 p-3 gap-3 items-center self-stretch">
                <div class="flex flex-col w-full gap-1 items-start md:flex-row">
                    <span class="text-gray-500 text-sm w-full max-w-[140px] truncate">{{ $t('public.date') }}</span>
                    <span class="text-gray-950 text-sm w-full font-medium truncate">{{ dayjs(data.created_at).format('YYYY/MM/DD') }}</span>
                </div>
                <div v-if="data.transaction_type === 'withdrawal' || data.transaction_type === 'transfer'" class="flex flex-col w-full gap-1 items-start md:flex-row">
                    <span class="text-gray-500 text-sm w-full max-w-[140px] truncate">{{ $t('public.transaction_id') }}</span>
                    <span class="text-gray-950 text-sm w-full font-medium truncate">{{ data.transaction_number }}</span>
                </div>
                <div class="flex flex-col w-full gap-1 items-start md:flex-row">
                    <span class="text-gray-500 text-sm w-full max-w-[140px] truncate">{{ $t('public.description') }}</span>
                    <span class="text-gray-950 text-sm w-full font-medium truncate">
                        <template v-if="data.transaction_type === 'transfer_to_account'">
                            {{ $t('public.to') }} {{ data.to_meta_login }}
                        </template>
                        <template v-else>
                            {{ $t(`public.${data.transaction_type}`) }}
                        </template>
                    </span>
                </div>
                <div v-if="data.transaction_type === 'rebate_payout'" class="flex flex-col w-full gap-1 items-start md:flex-row">
                    <span class="text-gray-500 text-sm w-full max-w-[140px] truncate">{{ $t('public.account_type') }}</span>
                    <span class="text-gray-950 text-sm w-full font-medium truncate">{{ $t(`public.${data.account_type}`) }}</span>
                </div>
                <div v-if="data.transaction_type === 'rebate_payout'" class="flex flex-col w-full gap-1 items-start md:flex-row">
                    <span class="text-gray-500 text-sm w-full max-w-[140px] truncate" >{{ $t('public.total_volume') }}</span>
                    <span class="text-gray-950 text-sm w-full font-medium truncate">{{ `$&nbsp;${formatAmount(data.total_volume)} Ł` }}</span>
                </div>
                <div class="flex flex-col w-full gap-1 items-start md:flex-row">
                    <span class="text-gray-500 text-sm w-full max-w-[140px] truncate" >{{ $t('public.amount') }}</span>
                    <span class="text-sm w-full font-medium truncate" :class="getAmountTextColor(data.transaction_type)">{{ `$&nbsp;${formatAmount(data.amount)}` }}</span>
                </div>
            </div>
            <!-- v-if for the 2 div below -->
            <div v-if="data.transaction_type === 'withdrawal'" class="flex flex-col bg-gray-50 p-3 gap-3 items-center self-stretch">
                <div class="flex flex-col w-full gap-1 items-start md:flex-row">
                    <span class="text-gray-500 text-sm w-full max-w-[140px] truncate">{{ $t('public.wallet_name') }}</span>
                    <span class="text-gray-950 text-sm w-full font-medium truncate">{{ data.wallet_name }}</span>
                </div>
                <div class="flex flex-col w-full gap-1 items-start md:flex-row">
                    <span class="text-gray-500 text-sm w-full max-w-[140px] truncate">{{ $t('public.receiving_address') }}</span>
                    <span class="text-gray-950 text-sm w-full font-medium truncate">{{ data.to_wallet_address }}</span>
                </div>
            </div>

            <div v-if="data.transaction_type === 'withdrawal' || data.transaction_type === 'rebate_in' || data.transaction_type === 'rebate_out'" class="flex flex-col bg-gray-50 p-3 gap-3 items-center self-stretch">
                <div class="flex flex-col w-full gap-1 items-start md:flex-row">
                    <span class="text-gray-500 text-sm w-full max-w-[140px] truncate">{{ $t('public.remarks') }}</span>
                    <span class="text-gray-950 text-sm w-full font-medium truncate">{{ data.remarks }}</span>
                </div>
            </div>
        </div>
    </Dialog>
</template>