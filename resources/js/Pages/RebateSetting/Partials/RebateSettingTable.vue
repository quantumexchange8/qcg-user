<script setup>
import {ref, watch, watchEffect} from "vue";
import InputText from 'primevue/inputtext';
import Button from '@/Components/Button.vue';
import {useForm, usePage} from '@inertiajs/vue3';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import {FilterMatchMode} from "@primevue/core/api";
import Loader from "@/Components/Loader.vue";
import Select from "primevue/select";
import {
    IconSearch,
    IconCircleXFilled,
    IconAdjustmentsHorizontal,
} from '@tabler/icons-vue';
import InputNumber from "primevue/inputnumber";
import Empty from "@/Components/Empty.vue";
import Dialog from "primevue/dialog";
import InputError from "@/Components/InputError.vue";
import { wTrans, trans } from "laravel-vue-i18n";
import toast from '@/Composables/toast';

const props = defineProps({
    accountTypes: Array,
})

// Emit event back to parent
const emit = defineEmits(['update:accountType']);

const accountTypes = ref();

// Watch for changes in the accountTypes prop
watch(() => props.accountTypes, (newAccountTypes) => {
    accountTypes.value = newAccountTypes;
}, { immediate: true }); // immediate: true will execute the watcher immediately on component mount

const accountType = ref(accountTypes.value[0].value);
const loading = ref(false);
const dt = ref();
const agents = ref();

// Function to fetch agents based on the current account type
const getResults = async (type_id) => {
    loading.value = true;

    try {
        const response = await axios.get(`/rebate_setting/getAgents?type_id=${type_id}`);
        agents.value = response.data;
    } catch (error) {
        console.error('Error getting agents:', error);
    } finally {
        loading.value = false;
    }
};

// Fetch results initially using the current accountType
getResults(accountType.value);

// Watch for changes in accountType and fetch new results accordingly
watch(accountType, (newValue) => {
    emit('update:accountType', newValue);  // Emit the new value to the parent
    getResults(newValue);
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    upline_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    level: { value: null, matchMode: FilterMatchMode.EQUALS },
    role: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

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

const editingRows = ref([]);
const visible = ref(false);
const agentRebateDetail = ref();
const productDetails = ref();

const openDialog = (agentData) => {
    visible.value = true;
    agentRebateDetail.value = agentData.agent_data;
    productDetails.value = agentData.rebate_allocate;
}

const form = useForm({
    rebates: null
});
const onRowEditSave = (event) => {
    
    let { newData, index } = event;
    // console.log(editingRows);
    agents.value[index] = newData;
    // console.log('New Data:', newData);
    const data = agents.value[index].rebate_allocate;
    
    // Map the indexes (1, 2, 3, 4, 5) to the corresponding categories
    const categories = [
        { key: 1, name: 'forex' },
        { key: 2, name: 'stocks' },
        { key: 3, name: 'indices' },
        { key: 4, name: 'commodities' },
        { key: 5, name: 'cryptocurrency' }
    ];

    // Flag to track if the post should proceed
    let canPost = true;

    categories.forEach((category) => {
        // Get the value for the category
        const value = data[category.key];

        // Retrieve the upline and downline values dynamically
        const uplineMax = data[`upline_${category.name}`];
        const downlineMin = data[`downline_${category.name}`];

        // Prepare the messages by replacing the :name and :value placeholders
        const exceedUplineMessage = wTrans('public.rebate_exceed_upline', { name: trans('public.' + category.name), value: uplineMax });
        const exceedDownlineMessage = wTrans('public.rebate_exceed_downline', { name: trans('public.' + category.name), value: downlineMin });

        // Check if the value exceeds the upline max or falls below the downline min
        if (value > uplineMax) {
            // Show a warning message for exceeding the upline
            toast.add({ 
                type: 'warning', 
                title: exceedUplineMessage,
            });
            canPost = false; // Set flag to false, prevent form post
        } else if (value < downlineMin) {
            // Show a warning message for falling below the downline
            toast.add({ 
                type: 'warning', 
                title: exceedDownlineMessage,
            });
            canPost = false; // Set flag to false, prevent form post
        }
    });

    // Proceed with the form post only if all checks pass
    if (canPost) {
        form.rebates = agents.value[index].rebate_allocate;
        form.post(route('rebate_setting.updateRebateAmount'));
    }
};

const submitForm = (submitData) => {
    form.rebates = submitData;

    // Map the indexes (1, 2, 3, 4, 5) to the corresponding categories
    const categories = [
        { key: 1, name: 'forex' },
        { key: 2, name: 'stocks' },
        { key: 3, name: 'indices' },
        { key: 4, name: 'commodities' },
        { key: 5, name: 'cryptocurrency' }
    ];

    // Flag to track if the post should proceed
    let canPost = true;

    // Loop over the categories to validate each one
    categories.forEach((category) => {
        // Get the value for the category from the submitData
        const value = submitData[category.key];

        // Retrieve the upline and downline values dynamically from the submitData
        const uplineMax = submitData[`upline_${category.name}`];
        const downlineMin = submitData[`downline_${category.name}`];

        // Prepare the messages by replacing the :name and :value placeholders
        const exceedUplineMessage = wTrans('public.rebate_exceed_upline', { name: trans('public.' + category.name), value: uplineMax });
        const exceedDownlineMessage = wTrans('public.rebate_exceed_downline', { name: trans('public.' + category.name), value: downlineMin });

        // Check if the value exceeds the upline max or falls below the downline min
        if (value > uplineMax) {
            // Show a warning message for exceeding the upline
            toast.add({ 
                type: 'warning', 
                title: exceedUplineMessage,
            });
            closeDialog();
            canPost = false; // Set flag to false, prevent form post
        } else if (value < downlineMin) {
            // Show a warning message for falling below the downline
            toast.add({ 
                type: 'warning', 
                title: exceedDownlineMessage,
            });
            closeDialog();
            canPost = false; // Set flag to false, prevent form post
        }
    });

    // Proceed with the form post only if all checks pass
    if (canPost) {
        form.post(route('rebate_setting.updateRebateAmount'), {
            onSuccess: () => {
                closeDialog();
                form.reset();
            },
        });
    }
};

const closeDialog = () => {
    visible.value = false;
}

</script>

<template>
    <div class="flex flex-col items-center justify-center py-5 px-3 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6 overflow-x-auto">
        <DataTable
            v-model:editingRows="editingRows"
            :filters="filters"
            :value="agents"
            tableStyle="min-width: 50rem"
            :globalFilterFields="['agent_data.name']"
            ref="dt"
            :loading="loading"
            table-style="min-width:fit-content"
            editMode="row"
            :dataKey="agents && agents.length ? agents.agent_id : 'id'"
            @row-edit-save="onRowEditSave"
        >
            <template #header>
                <div class="flex flex-col md:flex-row mb-5 gap-3 items-center self-stretch md:justify-between md:mb-6">
                    <div class="relative w-full md:w-60">
                        <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                            <IconSearch size="20" stroke-width="1.25" />
                        </div>
                        <InputText v-model="filters['global'].value" :placeholder="$t('public.search')" size="search" class="font-normal w-full md:w-60" />
                        <div
                            v-if="filters['global'].value !== null"
                            class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                            @click="clearFilterGlobal"
                        >
                            <IconCircleXFilled size="16" />
                        </div>
                    </div>
                    <Select
                        v-model="accountType"
                        :options="accountTypes"
                        optionLabel="name"
                        optionValue="value"
                        class="w-full md:w-60 font-normal"
                    />
                </div>
            </template>
            <template #empty><Empty :title="$t('public.empty_agent_title')" :message="$t('public.empty_agent_message')" /></template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loader />
                    <span class="text-sm text-gray-700">{{ $t('public.loading_direct_agents_caption') }}</span>
                </div>
            </template>
            <Column v-if="agents && agents.length" field="agent" class="w-auto px-3">
                <template #header>
                    <span>{{ $t('public.agent') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data.agent_data.name }}
                </template>
                <template #editor="{ data }">
                    <div class="flex items-center gap-3">
                        <span>{{ data.agent_data.name }}</span>
                    </div>
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="1" class="hidden md:table-cell" style="width:12.5%;">
                <template #header>
                    <span>{{ $t('public.forex') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data.rebate_allocate['1'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data.rebate_allocate[field]"
                        :minFractionDigits="2"
                        fluid
                        :invalid="!!form.errors[`rebates.${field}`]"
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                    <InputError :message="form.errors[`rebates.${field}`]" />
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="2" class="hidden md:table-cell" style="width:12.5%;">
                <template #header>
                    <span>{{ $t('public.stocks') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data.rebate_allocate['2'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data.rebate_allocate[field]"
                        :minFractionDigits="2"
                        fluid
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="3" class="hidden md:table-cell" style="width:12.5%;">
                <template #header>
                    <span>{{ $t('public.indices') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data.rebate_allocate['3'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data.rebate_allocate[field]"
                        :minFractionDigits="2"
                        fluid
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="4" class="hidden md:table-cell" style="width:12.5%;">
                <template #header>
                    <span class="w-12 lg:w-auto">{{ $t('public.commodities') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data.rebate_allocate['4'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data.rebate_allocate[field]"
                        :minFractionDigits="2"
                        fluid
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="5" class="hidden md:table-cell" style="width:12.5%;">
                <template #header>
                    <span class="w-12 lg:w-auto">{{ $t('public.cryptocurrency') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data.rebate_allocate['5'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data.rebate_allocate[field]"
                        :minFractionDigits="2"
                        fluid
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                </template>
            </Column>
            <Column v-if="agents && agents.length" :rowEditor="true" style="width: 10%;" class="hidden md:table-cell" bodyStyle="text-align:center">
                <template #roweditoriniticon>
                    <Button
                        variant="gray-text"
                        type="button"
                        size="sm"
                        iconOnly
                        pill
                    >
                        <IconAdjustmentsHorizontal size="16" stroke-width="1.25" />
                    </Button>
                </template>
            </Column>
            <Column v-if="agents && agents.length" style="width: 15%;" class="md:hidden table-cell px-3">
                <template #body="slotProps">
                    <Button
                        variant="gray-text"
                        type="button"
                        size="sm"
                        iconOnly
                        pill
                        @click="openDialog(slotProps.data)"
                    >
                        <IconAdjustmentsHorizontal size="16" stroke-width="1.25" />
                    </Button>
                </template>
            </Column>
        </DataTable>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.edit_rebate_details')"
        class="dialog-xs"
    >
        <div class="flex flex-col py-4 md:py-6 gap-6 items-center self-stretch">
            <!-- agent details -->
            <div class="w-full flex items-center gap-3">
                <div class="w-full flex flex-col items-start">
                    <div class="w-full truncate font-semibold text-gray-950">
                        {{ agentRebateDetail.name }}
                    </div>
                    <div class="w-full truncate text-gray-500 text-sm">
                        {{ agentRebateDetail.email }}
                    </div>
                </div>
            </div>

            <!-- rebate allocation -->
            <div class="w-full flex flex-col items-center self-stretch">
                <div class="flex justify-between items-center py-3 self-stretch border-b border-gray-100 bg-gray-50">
                    <div
                        class="flex items-center w-full px-3 text-gray-950 text-xs font-semibold uppercase">
                        {{ $t('public.product') }}
                    </div>
                    <div
                        class="flex items-center px-3 w-full text-gray-950 text-xs font-semibold uppercase">
                        {{ $t('public.upline_rebate') }} ($)
                    </div>
                    <div
                        class="flex items-center px-3 w-full text-gray-950 text-xs font-semibold uppercase">
                        {{ $t('public.rebate') }} / Ł ($)
                    </div>
                </div>

                <div class="flex flex-col items-center self-stretch max-h-[400px] overflow-y-auto">
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full">
                            {{ $t('public.forex') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_forex }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['1']"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full">
                            {{ $t('public.stocks') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_stocks }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['2']"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full">
                            {{ $t('public.indices') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_indices }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['3']"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full truncate">
                            {{ $t('public.commodities') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_commodities }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['4']"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full truncate">
                            {{ $t('public.cryptocurrency') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_cryptocurrency }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['5']"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
            <Button
                type="button"
                variant="gray-tonal"
                class="w-full md:w-[120px]"
                @click="closeDialog"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary-flat"
                class="w-full md:w-[120px]"
                @click="submitForm(productDetails)"
                :disabled="form.processing"
            >
                {{ $t('public.save') }}
            </Button>
        </div>
    </Dialog>

</template>
