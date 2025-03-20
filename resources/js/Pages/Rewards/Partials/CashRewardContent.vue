<script setup>
import { defineEmits, ref, watch } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Button from "@/Components/Button.vue";
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    reward_id: Number,
});

const emit = defineEmits(['update:visible']);

const transferOptions = ref([]);
const selectedAccount = ref(0);
const getOptions = async () => {
    try {
        const response = await axios.get('/accounts/getOptions');
        transferOptions.value = response.data.transferOptions;
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

getOptions();

const cashForm = useForm({
    reward_id: props.reward_id,
    reward_type: 'cash_rewards',
    meta_login: '',
    checkbox: false,
});

const submitForm = () => {
    cashForm.meta_login = selectedAccount.value.name;
    cashForm.post(route('rewards.redeemRewards'), {
        // onError: (errors) => {
        //     console.log(errors); // Handle validation errors
        // },
        onSuccess: () => {
            closeDialog();
        }
    })
};

const closeDialog = () => {
    emit('update:visible'); // Emit to close the dialog
};
</script>

<template>
    <form>
        <div class="flex flex-col gap-8">
            <div class="flex flex-col gap-2">
                <InputLabel for="meta_login" :value="$t('public.select_receiving_account')" />
                <Select
                    v-model="selectedAccount"
                    :options="transferOptions"
                    optionLabel="name"
                    :placeholder="$t('public.select')"
                    class="w-full"
                    scroll-height="236px"
                    :disabled="!transferOptions.length"
                    :invalid="!!cashForm.errors.meta_login"
                />
                <span class="self-stretch text-gray-500 text-xs">{{ $t('public.balance') }}: $ {{ selectedAccount ? selectedAccount.value : selectedAccount }}</span>
                <InputError :message="cashForm.errors.meta_login" />
            </div>
            <label class="flex items-center gap-2">
                <Checkbox binary v-model="cashForm.checkbox" class="w-4 h-4 flex-shrink-0" />
                <span class="text-gray-500 text-xs">{{ $t('public.redeem_term') }}</span>
            </label>
            <div class="grid grid-cols-2 justify-items-end items-center gap-4 self-stretch">
                <Button
                    type="button"
                    variant="gray-outlined"
                    @click="closeDialog"
                    class="w-full"
                    size="base"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    type="button"
                    variant="primary-flat"
                    @click="submitForm"
                    class="w-full text-nowrap"
                    size="base"
                    :disabled="!cashForm.checkbox"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </form>
</template>
