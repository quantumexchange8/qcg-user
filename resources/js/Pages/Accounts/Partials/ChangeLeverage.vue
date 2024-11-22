<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import {useForm} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue"
import InputError from "@/Components/InputError.vue";
import Select from "primevue/select";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";
import {ref, watch} from "vue";

const props = defineProps({
    account: Object,
})

const leverages = ref([]);
const emit = defineEmits(['update:visible'])

const getOptions = async () => {
    try {
        const response = await axios.get('/accounts/getOptions');
        leverages.value = response.data.leverages;
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

getOptions();

watch(leverages, (newLeverage) => {
    form.leverage = newLeverage[0].value
})

const form = useForm({
    account_id: props.account.id,
    leverage: '',
})

const submitForm = () => {
    form.post(route('accounts.change_leverage'), {
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
        <div class="flex flex-col items-center gap-8 self-stretch md:gap-10">
            <div class="flex flex-col py-6 w-full gap-8">
                <div class="flex flex-col gap-1 px-8 py-3 bg-gray-100">
                    <span class="w-full text-gray-500 text-center text-xs font-medium">#{{ account.meta_login }} - {{ $t('public.current_account_balance') }}</span>
                    <span class="w-full text-gray-950 text-center text-xl font-semibold">$ {{ account.balance }}</span>
                </div>

                <!-- input fields -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="leverage" :value="$t('public.leverage')" />
                    <Select
                        v-model="form.leverage"
                        :options="leverages"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.leverages_placeholder')"
                        class="w-full"
                        scroll-height="236px"
                        :invalid="!!form.errors.leverage"
                        :disabled="!leverages.length"
                    />
                    <InputError :message="form.errors.leverage" />
                </div>
            </div>
        </div>
        <div class="self-stretch">
            <div class="text-gray-500 text-xs">{{ $t('public.acknowledgement') }}
                <TermsAndCondition
                />.
            </div>
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
                :disabled="form.processing"
            >
                {{ $t('public.confirm') }}
            </Button>
        </div>
    </form>
</template>
