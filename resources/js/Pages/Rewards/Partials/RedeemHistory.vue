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
import StatusBadge from '@/Components/StatusBadge.vue';
import { trans, wTrans } from "laravel-vue-i18n";
import {useLangObserver} from "@/Composables/localeObserver.js";

const {locale} = useLangObserver();

const visible = ref(false);
const visible2 = ref(false);

const closeDialog = () => {
    visible.value = false;
}

const { formatAmount, formatDate } = transactionFormat();

const loading = ref(false);
const descriptions = [
    { name: wTrans('public.all'), value: 'all' },
    { name: wTrans('public.processing'), value: 'processing' },
    { name: wTrans('public.successful'), value: 'successful' },
    { name: wTrans('public.rejected'), value: 'rejected' },
];
const selectedDescription = ref(descriptions[0].value);
const dt = ref();
const redeems = ref();

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
        months.value = ['select_all', ...response.data.months];

        if (months.value.length) {
            selectedMonth.value = [getCurrentMonthYear()];
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
            const formattedMonth = selectedMonth === 'select_all' 
                ? 'select_all' 
                : dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');

            params.append('selectedMonth', formattedMonth);
        }

        // console.log(description)
        const response = await axios.get('/rewards/getRedeemHistory', { params });
        // console.log('Params:', params.toString());
        // console.log(response.data.redeems);
        redeems.value = response.data.redeems;
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
        <IconReport size="24" stroke-width="1.5" />
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.redemption_history')"
        class="dialog-xs md:dialog-md"
        :dismissableMask="true"
    >
        <div>
            <DataTable
                :value="redeems"
                removableSort
                :paginator="redeems?.length > 0"
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
                    <div class="flex flex-col items-center gap-4 mb-4 md:mb-8">
                        <div class="flex flex-col items-center gap-5 self-stretch md:flex-row">
                            <Select 
                                v-model="selectedMonth" 
                                :options="months" 
                                :placeholder="$t('public.month_placeholder')"
                                class="w-full md:w-60 font-normal truncate" scroll-height="236px" 
                            >
                                <template #option="{option}">
                                    <span class="text-sm">
                                        <template v-if="option === 'select_all'">
                                            {{ $t('public.select_all') }}
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
                <template #empty><Empty :title="$t('public.empty_redeem_history_title')" :message="$t('public.empty_redeem_history_message')" /></template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="redeems?.length > 0">
                <!-- <template> -->
                    <Column field="created_at" :header="$t('public.date')" sortable style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column field="reward_code" :header="$t('public.code')" sortable style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ slotProps.data.reward_code }}
                        </template>
                    </Column>
                    <Column field="transaction_amount" :header="`${$t('public.tp_used')}`" sortable style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ formatAmount(slotProps.data.transaction_amount) }}
                        </template>
                    </Column>
                    <Column field="status" :header="$t('public.status')" style="width: 25%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="flex py-1.5 items-center flex-1">
                                <StatusBadge :variant="slotProps.data.status">
                                    {{ $t(`public.${slotProps.data.status}`) }}
                                </StatusBadge>
                            </div>
                        </template>
                    </Column>
                    <Column class="md:hidden">
                        <template #body="slotProps">
                            <div class="flex items-center justify-between">
                                <div class="flex flex-col items-start">
                                    <div class="text-sm font-semibold">
                                        {{ slotProps.data.reward_code }}
                                    </div>
                                    <div class="text-gray-500 text-xs">
                                        {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                                    </div>
                                </div>
                                <div class="overflow-hidden text-right text-ellipsis font-semibold">
                                    {{ formatAmount(slotProps.data.transaction_amount) }} tp
                                </div>
                            </div>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </Dialog>

    <Dialog v-model:visible="visible2" modal :header="$t('public.redemption_details')" class="dialog-xs md:dialog-md" :dismissableMask="true">
        <div class="flex flex-col justify-center items-center gap-3 self-stretch pt-4 md:pt-6">         
            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.requested_date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ dayjs(data.created_at).format('YYYY/MM/DD HH:mm:ss') }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.processing_date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.approved_at ? dayjs(data.approved_at).format('YYYY/MM/DD HH:mm:ss') : '-' }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.points_used') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ `${formatAmount(data?.transaction_amount || 0)}&nbsp;tp` }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.status') }}</span>
                    <StatusBadge :variant="data.status" :value="$t('public.' + data.status)"/>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.rewards_code') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.reward_code || '-' }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.rewards_name') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.reward_type === 'cash_rewards' ? '💰 ' : '🎁 '  }}{{ data?.reward_name[locale] || '-' }}</span>
                </div>
                <div v-if="data?.reward_type === 'cash_rewards'" class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.receiving_account') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.receiving_account || '-' }}</span>
                </div>
                <div v-else class="w-full flex flex-col gap-3">
                    <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                        <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.recipient_name') }}</span>
                        <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.recipient_name || '-' }}</span>
                    </div>
                    <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                        <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.phone_number') }}</span>
                        <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.phone_number || '-' }}</span>
                    </div>
                    <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                        <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.provided_address') }}</span>
                        <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.address || '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.remarks') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.remarks || '-' }}</span>
                </div>
            </div>
        </div>
    </Dialog>
</template>