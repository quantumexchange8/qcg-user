<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import InputNumber from 'primevue/inputnumber';
import {useForm} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue"
import InputError from "@/Components/InputError.vue";
import Select from "primevue/select";
import {ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    rebateWallet: Object,
})

const transferOptions = ref([]);
const transferAmount = ref(0);
const {formatAmount} = transactionFormat()
const emit = defineEmits(['update:visible'])

const getOptions = async () => {
    try {
        const response = await axios.get('/accounts/getOptions');
        transferOptions.value = response.data.transferOptions;
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

getOptions();

const form = useForm({
    wallet_id: props.rebateWallet.id,
    amount: 0,
    meta_login: '',
})

watch(transferOptions, (newAccount) => {
    if (Array.isArray(newAccount) && newAccount.length > 0) {
        transferAmount.value = newAccount[0].value;
    } else {
        transferAmount.value = 0; 
    }
});

// Watch for changes in transferAmount to update form.meta_login
watch(transferAmount, (newValue) => {
    const selectedOption = transferOptions.value.find(option => option.value === newValue);
    if (selectedOption) {
        form.meta_login = selectedOption.name; // Update the account name
    } else {
        form.meta_login = ''; // Reset if no valid selection
    }
});

const toggleFullAmount = () => {
    if (form.amount) {
        form.amount = 0;
    } else {
        form.amount = Number(props.rebateWallet.balance);
    }
};

const submitForm = () => {
    form.post(route('dashboard.walletTransfer'), {
        onSuccess: () => {
            closeDialog();
        }
    });
}

const closeDialog = () => {
    emit('update:visible', false)
}
</script>

<template>
    <form>
        <div class="flex flex-col items-center gap-8 pt-6 self-stretch md:gap-10">
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div class="flex flex-col justify-center items-center py-3 px-8 gap-1 self-stretch bg-gray-100">
                    <span class="w-full text-gray-500 text-center text-xs">{{ $t('public.available_rebate_balance') }}</span>
                    <span class="w-full text-gray-950 text-center text-lg font-semibold">$ {{ formatAmount(rebateWallet.balance) }}</span>
                </div>

                <!-- input fields -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="receiving_wallet" :value="$t('public.transfer_to')" />
                    <Select
                        v-model="transferAmount"
                        :options="transferOptions"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.select')"
                        class="w-full"
                        scroll-height="236px"
                        :invalid="!!form.errors.meta_login"
                        :disabled="!transferOptions.length"
                    />
                    <InputError :message="form.errors.meta_login" />
                    <span v-if="transferOptions.length" class="self-stretch text-gray-500 text-xs">{{ $t('public.balance') }}: $ {{ transferOptions.length ? formatAmount(transferAmount, 0) : $t('public.loading_caption')}}</span>
                    <span v-else class="self-stretch text-gray-500 text-xs">{{ $t('public.no_account_transfer') }}</span>
                </div>

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
                    <InputError :message="form.errors.amount" />
                </div>
            </div>
        </div>
        <div class="flex justify-end items-center pt-5 gap-4 self-stretch sm:pt-7">
            <Button
                type="button"
                variant="gray-outlined"
                class="w-full"
                @click.prevent="closeDialog()"
                :disabled="form.processing"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary-flat"
                class="w-full"
                @click.prevent="submitForm"
                :disabled="form.processing || !transferOptions.length"
            >
                {{ $t('public.transfer') }}
            </Button>
        </div>
    </form>
</template>
