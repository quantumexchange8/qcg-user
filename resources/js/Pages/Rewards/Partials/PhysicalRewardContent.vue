<script setup>
import { defineEmits, ref, watch } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import TextArea from "primevue/textarea";
import Button from "@/Components/Button.vue";
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    reward_id: Number,
});

const emit = defineEmits(['update:visible']);

const countryList = ref([]); 
const selectedCountry = ref();

const getCountries = async () => {
    try {
        const response = await axios.get('/rewards/getCountryPhones');
        countryList.value = response.data.countries;
    } catch (error) {
        console.error('Error getting countries:', error);
    }
};

getCountries();

const physicalForm = useForm({
    reward_id: props.reward_id,
    reward_type: 'physical_rewards',
    recipient_name: '',
    dial_code: '',
    phone: '',
    phone_number: '',
    address: '',
    checkbox: false,
});

const submitForm = () => {
    if(selectedCountry.value){
        physicalForm.dial_code = selectedCountry.value.phone_code;
        physicalForm.phone_number = selectedCountry.value.phone_code + physicalForm.phone;
    }
    closeDialog();
    physicalForm.post(route('rewards.redeemRewards'), {
        onError: (errors) => {
            console.log(errors); // Handle validation errors
        },
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
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <InputLabel for="recipient_name" :value="$t('public.recipient_name')" />
                    <InputText
                        id="recipient_name"
                        type="text"
                        class="block w-full"
                        :placeholder="$t('public.full_name')"
                        autofocus
                        v-model="physicalForm.recipient_name"
                        :invalid="!!physicalForm.errors.recipient_name"
                    />
                    <InputError :message="physicalForm.errors.recipient_name" />
                </div>

                <div class="flex flex-col gap-2">
                    <InputLabel
                        for="phone"
                        :value="$t('public.phone_number')"
                    />
                    <div class="flex gap-2 items-center">
                        <Select filter :filterFields="['name', 'phone_code']" v-model="selectedCountry" :options="countryList" optionLabel="name" :placeholder="$t('public.phone_code')" class="min-w-[120px] max-w-[120px]"
                        :invalid="!!physicalForm.errors.dial_code">
                            <template #value="slotProps" >
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div class="text-black">{{ slotProps.value.phone_code }}
                                    </div>
                                </div>
                                <span v-else>{{ $t('public.phone_code') }}</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center">
                                    <div class="text-black">{{ slotProps.option.name }} ({{ slotProps.option.phone_code }})</div>
                                </div>
                            </template>
                        </Select>

                        <InputText
                            id="phone"
                            type="text"
                            class="block w-full"
                            :placeholder="$t('public.phone_number')"
                            v-model="physicalForm.phone"
                            :invalid="!!physicalForm.errors.phone"
                        />
                    </div>
                    <InputError :message="physicalForm.errors.dial_code"/>
                    <InputError :message="physicalForm.errors.phone"/>
                </div>

                <div class="flex flex-col gap-2">
                    <InputLabel
                        for="address"
                        :value="$t('public.address')"
                    />
                    <TextArea
                        id="address"
                        type="text"
                        class="w-full h-20"
                        v-model="physicalForm.address"
                        :placeholder="$t('public.full_address')"
                        :invalid="!!physicalForm.errors.address"
                        rows="5"
                        cols="30"
                    />
                    <span class="text-xs text-gray-500">{{ $t('public.address_term') }}</span>
                    <InputError :message="physicalForm.errors.address" />
                </div>
            </div>
            <label class="flex items-center gap-2">
                <Checkbox binary v-model="physicalForm.checkbox" class="w-4 h-4 flex-shrink-0" />
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
                    :disabled="!physicalForm.checkbox"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </form>
</template>
