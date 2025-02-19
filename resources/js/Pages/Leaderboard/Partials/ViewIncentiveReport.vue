<script setup>
import Button from "@/Components/Button.vue"
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { IconX, IconSearch, IconDownload } from "@tabler/icons-vue";
import { transactionFormat } from "@/Composables/index.js";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import Select from 'primevue/select';
import dayjs from 'dayjs'
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import Dialog from "primevue/dialog";
import { trans, wTrans } from "laravel-vue-i18n";

const { formatAmount, formatDate } = transactionFormat();

const props = defineProps({
    profile: Object,
})

const emit = defineEmits(['update:visible'])

const closeDialog = () => {
    emit('update:visible', false);
}

const visible = ref(false)
const loading = ref(false);
const dt = ref();
const bonuses = ref()
const totalBonusAmount = ref(0);

const months = ref([]);
const selectedMonth = ref('');

const getCurrentMonthYear = () => {
    const date = new Date();
    return `01 ${dayjs(date).format('MMMM YYYY')}`;
};

// Fetch settlement months from API
const getIncentiveMonths = async () => {
    try {
        const response = await axios.get('/getIncentiveMonths');
        months.value = ['select_all', ...response.data.months];

        if (months.value.length) {
            selectedMonth.value = [getCurrentMonthYear()];
        }
    } catch (error) {
        console.error('Error incentive months:', error);
    }
};

getIncentiveMonths()

const getResults = async (selectedMonth = '') => {
    loading.value = true;

    try {
        let url = `/leaderboard/getStatementData?profile_id=${props.profile.id}`;

        if (selectedMonth) {
            const formattedMonth = selectedMonth === 'select_all' 
                ? 'select_all' 
                : dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');

            url += `&selectedMonth=${formattedMonth}`;
        }

        const response = await axios.get(url);
        bonuses.value = response.data.bonuses;
        totalBonusAmount.value = response.data.totalBonusAmount;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

watch(selectedMonth, (newMonth) => {
    getResults(newMonth);
});

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = bonuses.value;

    // Specify the headers
    const headers = [
        trans('public.date'),
        trans('public.target') + ' ($)',
        trans('public.achieved') + ' ($)',
        trans('public.rate') + ' (%)',
        trans('public.amount') + ' ($)',
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        return [
            obj.created_at !== undefined ? dayjs(obj.created_at).format('YYYY/MM/DD') : '',
            obj.target_amount !== undefined ? obj.target_amount : '',
            obj.achieved_amount !== undefined ? obj.achieved_amount : '',
            obj.incentive_rate !== undefined ? obj.incentive_rate : '',
            obj.incentive_amount !== undefined ? obj.incentive_amount : '',
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
</script>

<template>
    <Button
        type="button"
        variant="primary-text"
        @click="visible = true"
    >
    {{ $t('public.view_incentive_report') }}
    </Button>
    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.view_incentive_report')"
        class="dialog-xs md:dialog-lg"
    >
        <div class="w-full flex flex-col justify-center items-center pt-4 gap-4 self-stretch md:pt-6 md:gap-8">
            <DataTable
                :value="bonuses"
                removableSort
                scrollable
                scrollHeight="400px"
                tableStyle="md:min-width: 50rem"
                ref="dt"
                :loading="loading"
            >
                <template #header>
                    <div class="flex flex-col items-center gap-4 mb-4 md:mb-8">
                        <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:justify-between">
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
                            <Button
                                variant="primary-outlined"
                                @click="bonuses?.length > 0 ? exportXLSX($event) : null" 
                                class="w-full md:w-auto"
                            >
                                <IconDownload size="20" stroke-width="1.25" />
                                {{ $t('public.export') }}
                            </Button>
                        </div>
                        <div v-if="bonuses?.length > 0" class="flex items-center gap-3 self-stretch md:hidden">
                            <span class="text-gray-500 text-sm font-normal">{{ $t('public.total') }}:</span>
                            <span class="text-gray-950 font-semibold">$&nbsp;{{ formatAmount(totalBonusAmount) }}</span>
                        </div>
                    </div>
                </template>
                <template #empty><Empty :title="$t('public.empty_records_title')" :message="$t('public.empty_report_message')" /></template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="bonuses?.length > 0">
                <!-- <template> -->
                    <Column field="created_at" :header="$t('public.date')" sortable style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column field="target_amount" :header="`${$t('public.target')}&nbsp;(${profile.sales_category === 'trade_volume' ? 'Ł' : '$'})`" style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ formatAmount(slotProps.data.target_amount) }}
                        </template>
                    </Column>
                    <Column field="achieved_amount" :header="`${$t('public.achieved')}&nbsp;(${profile.sales_category === 'trade_volume' ? 'Ł' : '$'})`" style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ formatAmount(slotProps.data.achieved_amount) }}
                        </template>
                    </Column>
                    <Column field="incentive_rate" :header="`${$t('public.rate')}&nbsp;(${profile.sales_category === 'trade_volume' ? '$' : '%'})`" style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ formatAmount(slotProps.data.incentive_rate) }}
                        </template>
                    </Column>
                    <Column field="incentive_amount" :header="`${$t('public.incentive')}&nbsp;($)`" sortable style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            {{ formatAmount(slotProps.data.incentive_amount) }}
                        </template>
                    </Column>
                    <ColumnGroup type="footer">
                        <Row>
                            <Column class="hidden md:table-cell" :footer="$t('public.total') + ' ($) :'" :colspan="4" footerStyle="text-align:right" />
                            <Column class="hidden md:table-cell" :footer="formatAmount(totalBonusAmount ? totalBonusAmount : 0)" />
                        </Row>
                    </ColumnGroup>
                    <Column style="width: 50%" class="md:hidden" headerClass="hidden">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start gap-1 self-stretch">
                                <span class="self-stretch text-gray-950 font-semibold">{{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}</span>
                                <div class="flex items-center gap-1 text-gray-700 text-xs">
                                    <div>
                                        <span v-if="profile.sales_category !== 'trade_volume'">$&nbsp;</span>{{ formatAmount(slotProps.data.achieved_amount) }}<span v-if="profile.sales_category === 'trade_volume'">&nbsp;Ł</span>
                                    </div>
                                    <span class="text-gray-500">|</span>
                                    <div>
                                        <span v-if="profile.sales_category !== 'trade_volume'">$&nbsp;</span>{{ formatAmount(slotProps.data.target_amount) }}<span v-if="profile.sales_category === 'trade_volume'">&nbsp;Ł</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column style="width: 50%" class="md:hidden" headerClass="hidden">
                        <template #body="slotProps">
                            <div class="flex flex-col items-end gap-1">
                                <span class="self-stretch text-gray-950 text-right font-semibold">$&nbsp;{{ formatAmount(slotProps.data.incentive_amount) }}</span>
                                <div class="flex justify-end items-center gap-1 self-stretch text-xs">
                                    <span class="text-gray-700">{{ $t('public.rate') }}:</span>
                                    <div class="text-gray-500 text-right">
                                        <span v-if="profile.sales_category === 'trade_volume'">$&nbsp;</span>{{ formatAmount(slotProps.data.incentive_rate) }}<span v-if="profile.sales_category !== 'trade_volume'">&nbsp;%</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </Dialog>

</template>
