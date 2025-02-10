<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import InputNumber from 'primevue/inputnumber';
import {useForm} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue"
import InputError from "@/Components/InputError.vue";
import Select from "primevue/select";
import {ref, watch, computed} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";
import Checkbox from 'primevue/checkbox';

const props = defineProps({
    account: Object,
    type: String,
})

const walletOptions = ref([]);
const {formatAmount} = transactionFormat()
const emit = defineEmits(['update:visible'])

const getOptions = async () => {
    try {
        const response = await axios.get('/accounts/getOptions');
        walletOptions.value = response.data.walletOptions;
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

getOptions();

watch(walletOptions, (newWallet) => {
    form.wallet_address = newWallet[0].value
})

const form = useForm({
    account_id: props.account.id,
    amount: 0,
    wallet_address: '',
    checkbox1: false,
    checkbox2: false,
})

const toggleFullAmount = () => {
    if (form.amount) {
        form.amount = 0;
    } else {
        form.amount = Number(props.account.balance);
    }
};

const submitForm = () => {
    form.post(route('accounts.accountWithdrawal'), {
        onSuccess: () => {
            closeDialog();
        }
    })
}

const closeDialog = () => {
    emit('update:visible', false)
}

const isFormValid = computed(() => {
    if (props.account.account_category === 'promotion') {
        return form.checkbox1 && form.checkbox2;
    }
    else {
        return form.checkbox2;
    }
});
</script>

<template>
    <form>
        <div class="flex flex-col items-center self-stretch gap-8 py-6">
            <div class="flex flex-col w-full gap-5">
                <div class="flex flex-col gap-1 px-8 py-3 bg-gray-100">
                    <span class="w-full text-gray-500 text-center text-xs font-medium">#{{ account.meta_login }} - {{ $t('public.current_account_balance') }}</span>
                    <span class="w-full text-gray-950 text-center text-xl font-semibold">$ {{ formatAmount(account.balance) }}</span>
                </div>

                <!-- input fields -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="amount" :value="$t('public.amount')" />
                    <div class="relative w-full">
                        <InputNumber
                            v-model="form.amount"
                            inputId="currency-us"
                            prefix="$ "
                            class="w-full"
                            inputClass="py-3 px-4"
                            :min="0"
                            :step="100"
                            :minFractionDigits="2"
                            fluid
                            autofocus
                            :invalid="!!form.errors.amount"
                        />
                        <div
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 cursor-pointer text-sm font-semibold"
                            :class="{
                                    'text-primary-500': !form.amount,
                                    'text-error-500': form.amount,
                                }"
                            @click="toggleFullAmount"
                        >
                            {{ form.amount ? $t('public.clear') : $t('public.full_amount') }}
                        </div>
                    </div>
                    <span class="self-stretch text-gray-500 text-xs">{{ $t('public.minimum_amount') }}: ${{ formatAmount(50) }}</span>
                    <InputError :message="form.errors.amount" />
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
            <div class="flex flex-col gap-4">
                <label v-if="type==='promotion'" class="flex items-center gap-2">
                    <Checkbox binary v-model="form.checkbox1" class="w-4 h-4 flex-shrink-0" />
                    <span class="text-gray-500 text-xs">{{ $t('public.withdrawal_term_1') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <Checkbox binary v-model="form.checkbox2" class="w-4 h-4 flex-shrink-0" />
                    <span class="text-gray-500 text-xs">{{ $t('public.withdrawal_term_2') }}</span>
                </label>
            </div>
        </div>
        <div class="flex justify-end items-center pt-5 gap-4 self-stretch sm:pt-6">
            <Button
                type="button"
                variant="gray-tonal"
                class="w-full "
                @click="closeDialog"
                :disabled="form.processing"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary-flat"
                class="w-full "
                @click="submitForm"
                :disabled="form.processing || !walletOptions.length || !isFormValid"
            >
                {{ $t('public.confirm') }}
            </Button>
        </div>
    </form>
</template>
