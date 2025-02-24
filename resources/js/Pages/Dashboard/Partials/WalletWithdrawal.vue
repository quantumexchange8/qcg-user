<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import InputNumber from 'primevue/inputnumber';
import {useForm} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue"
import InputError from "@/Components/InputError.vue";
import Select from "primevue/select";
import {ref, watch, computed} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Checkbox from 'primevue/checkbox';


const props = defineProps({
    wallet: Object,
    terms: Object,
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

const form = useForm({
    wallet_id: props.wallet.id,
    amount: 0,
    wallet_address: '',
    checkbox: false,
})

watch(walletOptions, (newWallet) => {
    form.wallet_address = newWallet[0].value
})

const toggleFullAmount = () => {
    if (form.amount) {
        form.amount = 0;
    } else {
        form.amount = Number(props.wallet.balance);
    }
};

const submitForm = () => {
    form.post(route('dashboard.walletWithdrawal'), {
        onSuccess: () => {
            closeDialog();
        }
    });
}

const closeDialog = () => {
    emit('update:visible', false)
}

const isFormValid = computed(() => form.checkbox);
</script>

<template>
    <form>
        <div class="flex flex-col items-center gap-8 pt-6 self-stretch md:gap-10">
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div class="flex flex-col justify-center items-center py-3 px-8 gap-1 self-stretch bg-gray-100">
                    <span class="w-full text-gray-500 text-center text-xs">{{ wallet.type === 'rebate_wallet' ? $t('public.available_rebate_balance') : $t('public.available_bonus_balance') }}</span>
                    <span class="w-full text-gray-950 text-center text-lg font-semibold">$ {{ formatAmount(wallet.balance) }}</span>
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
            <label class="flex items-center gap-2">
                <Checkbox binary v-model="form.checkbox" class="w-4 h-4 flex-shrink-0" />
                <span class="text-gray-500 text-xs">{{ $t('public.withdrawal_term_2') }}</span>
            </label>
        </div>
        <div class="flex justify-end items-center pt-5 gap-4 self-stretch sm:pt-7">
            <Button
                type="button"
                variant="gray-tonal"
                class="w-full md:w-[120px]"
                @click.prevent="closeDialog()"
                :disabled="form.processing"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary-flat"
                class="w-full md:w-[120px]"
                @click.prevent="submitForm"
                :disabled="form.processing || !walletOptions.length || !isFormValid"
            >
                {{ $t('public.confirm') }}
            </Button>
        </div>
    </form>
</template>
