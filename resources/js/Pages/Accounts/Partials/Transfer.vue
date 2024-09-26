<script setup>
import Button from "@/Components/Button.vue";
import {ref, watchEffect} from "vue";
import {useForm, usePage, Link} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/InputLabel.vue";
import Dialog from "primevue/dialog";
import InputNumber from 'primevue/inputnumber';
import {transactionFormat} from "@/Composables/index.js";

// const props = defineProps({
//     masterAccount: Object,
//     terms: Object,
// })

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

};

const leverages = ref();
const selectedLeverage = ref();

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
        type="button" variant="primary-outlined" class="w-full px-4 py-2"
        @click="visible = true"
    >
        {{ $t('public.open_demo') }}
    </Button>

    <Dialog v-model:visible="visible" modal :header="$t('public.open_demo')" class="dialog-sm">
        <form >
            <div class="flex flex-col w-full py-6 gap-8">
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-2">
                        <Label
                            for="balance_amount"
                            :value="$t('public.balance_amount')"
                        />
                        <InputNumber v-model="amount" inputId="currency-us" mode="currency" currency="USD" locale="en-US" fluid />
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label
                            for="leverage"
                        >{{ $t('public.leverage') }}</Label>
                        <Select v-model="selectedLeverage" :options="leverages" :placeholder="$t('public.select_leverage')" class="w-full" />
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
