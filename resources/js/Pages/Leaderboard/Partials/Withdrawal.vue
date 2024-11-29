<script setup>
import Dialog from "primevue/dialog";
import ConfirmDialog from 'primevue/confirmdialog';
import Button from "@/Components/Button.vue";
import {transactionFormat} from "@/Composables/index.js";
import InputLabel from "@/Components/InputLabel.vue";
import InputNumber from 'primevue/inputnumber';
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import Select from "primevue/select";
import {ref, watch} from "vue";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";

const props = defineProps({
    incentiveWallet: Object,
})

const walletOptions = ref([]);
const getOptions = async () => {
    try {
        const response = await axios.get('/accounts/getOptions');
        walletOptions.value = response.data.walletOptions;
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

getOptions();


const { formatAmount } = transactionFormat();

const visible = ref(false);
const agreementVisible = ref(false)
const confirmVisible = ref(false)


const form = useForm({
    wallet_id: '',
    amount: 0,
    wallet_address: '',
})

watch(walletOptions, (newWallet) => {
    form.wallet_address = newWallet[0].value
})

// const submitForm = () => {
//     form.post(route('leaderboard.incentiveWithdrawal'), {
//         onSuccess: () => {
//             closeDialog();
//         }
//     });
// }

const closeDialog = () => {
    visible.value = false;
}

</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        class="flex-1"
        @click="visible = true"
    >
        {{ $t('public.withdrawal') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.incentive_withdrawal')"
        class="dialog-xs sm:dialog-sm"
    >
        <form>
            <div class="flex flex-col w-full py-6 gap-8">
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1 px-8 py-3 bg-gray-100">
                        <span class="text-xs text-center text-gray-500">{{ $t('public.available_incentive') }}</span>
                        <span class="text-lg text-center font-bold text-gray-950">$ {{ formatAmount(props.incentiveWallet.balance) }}</span>
                    </div>
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel for="receiving_wallet" :value="$t('public.receiving_wallet')" />
                        <Select
                            v-model="form.wallet_address"
                            :options="walletOptions"
                            optionLabel="name"
                            optionValue="value"
                            :placeholder="$t('public.receiving_wallet_placeholder')"
                            class="w-full"
                            scroll-height="236px"
                            :invalid="!!form.errors.wallet_address"
                            :disabled="!walletOptions.length"
                        />
                        <InputError :message="form.errors.wallet_address" />
                        <span class="self-stretch text-gray-500 text-xs">{{ walletOptions.length ? form.wallet_address : $t('public.loading_caption')}}</span>
                    </div>
                </div>
                <div class="self-stretch">
                    <div class="text-gray-500 text-xs">{{ $t('public.acknowledgement') }}
                        <TermsAndCondition
                        />.
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-6 w-full">
                <Button variant="gray-outlined" type="button" class="justify-center" @click="closeDialog">
                    {{$t('public.cancel')}}
                </Button>
                <Button variant="primary-flat" type="button" class="justify-center" @click="submitForm" :disabled="form.processing">{{$t('public.confirm')}}</Button>
            </div>
        </form>
    </Dialog>
    
</template>
