<script setup>
import Button from "@/Components/Button.vue";
import {ref, watchEffect} from "vue";
import {useForm, usePage, Link} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/InputLabel.vue";
import Dialog from "primevue/dialog";
import Chip from "primevue/chip";
import Select from "primevue/select";
import {transactionFormat} from "@/Composables/index.js";
import {IconCircleCheckFilled} from "@tabler/icons-vue";

const props = defineProps({
    account_types: Array,
    leverages: Array,
})

const visible = ref(false)
const agreementVisible = ref(false)

const closeDialog = () => {
    visible.value = false;
}

const form = useForm({
    account_type: '',
    leverage: '',
});

const submitForm = () => {
    form.leverage = selectedLeverage.value.value;
    form.post(route('accounts.createTradingAccount'), {
        onSuccess: () => {
            form.reset();
        },
    })
};

const selectedLeverage = ref();
const chips = [
    {
        label: 'Standard Account',
        type: 'standard',
        value: 1,
        caption: 'Versatile account with flexible spreads, suitable for traders of all levels and trading strategies.',
    },
    {
        label: 'ECN',
        type: 'ecn',
        value: 3,
        caption: 'test',
    },
];

const handleChipClick = (value) => {
    form.account_type = value;
};

// const getResults = async () => {
//     try {
//         const response = await axios.get('/member/getFilterData');
//         countries.value = response.data.countries;
//         uplines.value = response.data.uplines;
//     } catch (error) {
//         console.error('Error changing locale:', error);
//     }
// };

// getResults();

// watchEffect(() => {
//     if (usePage().props.toast !== null) {
//         getResults();
//     }
// });
</script>

<template>
    <Button
        type="button" variant="primary-flat" class="w-full px-4 py-2"
        @click="visible = true"
    >
        {{ $t('public.open_account') }}
    </Button>

    <Dialog v-model:visible="visible" modal :header="$t('public.open_live')" class="dialog-sm">
        <form >
            <div class="flex flex-col w-full py-6 gap-8">
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-2">
                        <Label
                            for="select_account"
                            :value="$t('public.select_account')"
                        />
                        <div v-for="(chip, index) in chips" :key="index">
                            <Chip 
                                class="px-4 py-3 flex flex-col !gap-1 w-full !items-start"
                                :class="{
                                        'border-primary-600 !bg-primary-50': form.account_type === chip.value,
                                    }"
                                @click="handleChipClick(chip.value)"
                            >
                                <div class="flex flex-row gap-3 w-full justify-between">
                                    <span class="text-sm font-semibold text-gray-950">{{ chip.label }}</span>
                                    <IconCircleCheckFilled v-if="form.account_type === chip.value" size="20" color="#2E7D32" stroke-width="1.25"/>
                                </div>
                                <span class="text-xs text-gray-500">{{ chip.caption }}</span>
                            </Chip>
                        </div>
                        <!-- <Select v-model="selectedLeverage" :options="leverages" :placeholder="$t('public.select_leverage')" class="w-full" /> -->
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label
                            for="leverage"
                            :value="$t('public.leverage')"
                        />
                        <Select v-model="selectedLeverage" :options="props.leverages" :placeholder="$t('public.select_leverage')" class="w-full">
                            <template #value="slotProps" >
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div class="text-black">{{ slotProps.value.leverage }}
                                    </div>
                                </div>
                                <span v-else>{{ $t('public.select_leverage') }}</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center">
                                    <div class="text-black">{{ slotProps.option.leverage }}</div>
                                </div>
                            </template>
                        </Select>
                    </div>
                </div>
                <div class="flex justify-center gap-3 items-center self-stretch">
                    <span class="text-xs text-gray-700">
                        {{ $t('public.acknowledgement') }}
                        <Button
                            class="text-xs font-medium text-primary-500 hover:text-primary-600 focus:text-primary-600 !p-0"
                            @click="agreementVisible = true"
                            type="button"
                        >
                        {{ $t('public.trading_account_agreement') }}
                        </Button>.
                    </span>
                    
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-6 w-full">
                <Button variant="gray-outlined" type="button" class="justify-center" @click="closeDialog">
                    {{$t('public.cancel')}}
                </Button>
                <Button variant="primary-flat" type="button" class="justify-center" @click="submitForm" :disabled="form.processing">{{$t('public.open')}}</Button>
            </div>
        </form>
    </Dialog>

    <Dialog v-model:visible="agreementVisible" modal :header="$t('public.trading_account_agreement')" class="dialog-lg">
        <div class="flex flex-col pt-6 gap-8 text-sm">
            <span>{{ $t('public.trading_account_agreement_caption') }}</span>
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_1') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_1') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_2') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_2') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_3') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_3') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_4') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_4') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_5') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_5') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_6') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_6') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_7') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_7') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_8') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_8') }}</span>
                </div>
            </div>
        </div>
    </Dialog>

</template>
