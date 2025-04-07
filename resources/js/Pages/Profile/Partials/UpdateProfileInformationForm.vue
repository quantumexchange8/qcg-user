<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Select from 'primevue/select'
import Button from "@/Components/Button.vue"
import { useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import {ref, watch} from "vue";

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    countries: Array,
});

const countryList = ref(props.countries); 
const selectedCountry = ref();
const user = usePage().props.auth.user;

const form = useForm({
    name: user.chinese_name ?? user.first_name,
    email: user.email,
    dial_code: '',
    phone: user.phone,
    phone_number: '',
});

selectedCountry.value = countryList.value.find(country => country.phone_code === user.dial_code);

const dirtyFields = ref({
    dial_code: false,
    phone: false,
});

const handleInputChange = (field) => {
    dirtyFields.value[field] = true;
    // console.log(field);
};

const resetForm = () => {
    // Only reset fields that are marked as dirty
    if (dirtyFields.value.dial_code) {
        selectedCountry.value = countryList.value.find(country => country.phone_code === user.dial_code) || null;
        form.dial_code = user.dial_code || '';
    }

    if (dirtyFields.value.phone) {
        form.phone = user.phone || '';
    }

    // Reset dirty fields tracking
    dirtyFields.value = {
        dial_code: false,
        phone: false,
    };
};

const submitForm = () => {
    form.dial_code = selectedCountry.value.phone_code;

    if (selectedCountry.value) {
        form.phone_number = selectedCountry.value.phone_code + form.phone;
    }

    form.post(route('profile.update'), {
        onSuccess: () => {
            form.reset();
        },
    });
}
</script>

<template>
    <form class="w-full flex flex-col items-end p-3 gap-8 rounded-lg bg-white shadow-card md:p-6">
        <div class="w-full flex flex-col justify-center items-start gap-8">
            <div class="flex flex-col gap-1 items-start justify-center w-full">
                <span class="text-gray-950 font-bold">{{ $t('public.account_details') }}</span>
                <span class="text-gray-500 text-xs">{{ $t('public.update_account_caption') }}</span>
            </div>

            <div class="flex flex-col gap-5 items-center self-stretch w-full">
                <div class="flex flex-col gap-1 w-full">
                    <InputLabel for="name">
                        {{ $t('public.your_name') }}
                    </InputLabel>
                    <InputText
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        :placeholder="$t('public.name')"
                        :invalid="!!form.errors.name"
                        autocomplete="name"
                        disabled
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="flex flex-col gap-1 w-full">
                    <InputLabel for="email">
                        {{ $t('public.email') }}
                    </InputLabel>
                    <InputText
                        id="email"
                        type="email"
                        class="block w-full"
                        v-model="form.email"
                        :placeholder="$t('public.enter_email')"
                        :invalid="!!form.errors.email"
                        autocomplete="email"
                        disabled
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="flex flex-col gap-1 w-full">
                    <InputLabel for="phone">
                        {{ $t('public.phone_number') }}
                    </InputLabel>
                    <div class="flex gap-2 items-center self-stretch relative">
                        <Select
                            v-model="selectedCountry"
                            :options="countryList"
                            filter
                            :filterFields="['name', 'phone_code']"
                            optionLabel="name"
                            :placeholder="$t('public.phone_code')"
                            class="w-[110px]"
                            scroll-height="236px"
                            :invalid="!!form.errors.dial_code"
                            @change="handleInputChange('dial_code')"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div>{{ slotProps.value.phone_code }}</div>
                                </div>
                                <span v-else>
                                    {{ slotProps.placeholder }}
                                </span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center w-[262px] md:max-w-[236px]">
                                    <div>{{ slotProps.option.name }} <span class="text-gray-500">{{ slotProps.option.phone_code }}</span></div>
                                </div>
                            </template>
                        </Select>

                        <InputText
                            id="phone"
                            type="text"
                            class="block w-full"
                            v-model="form.phone"
                            :placeholder="$t('public.phone_number')"
                            :invalid="!!form.errors.phone"
                            @input="handleInputChange('phone')"
                        />
                    </div>
                    <!-- <div class="flex gap-3">
                        <Select 
                            filter 
                            :filterFields="['name', 'phone_code']" 
                            v-model="selectedCountry" 
                            :options="countryList" 
                            optionLabel="name" 
                            :placeholder="$t('public.phone_code')" 
                            class="min-w-[100px] max-w-[100px] xl:min-w-[180px] xl:max-w-[180px]"
                            @change="handleInputChange('dial_code')"
                            :disabled="!countries"
                            :invalid="form.errors.phone_code"
                        >
                            <template #value="slotProps" >
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div class="text-black">{{ slotProps.value.phone_code }}</div>
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
                            v-model="form.phone"
                            :invalid="form.errors.phone"
                        />
                    </div> -->
                    <!-- <InputError :message="form.errors.phone_code"/> -->
                    <InputError :message="form.errors.phone"/>
                </div>
            </div>
        </div>


        <div class="flex justify-end items-center gap-4 self-stretch">
            <Button
                type="button"
                variant="gray-tonal"
                :disabled="form.processing"
                @click="resetForm"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                type="button"
                variant="primary-flat"
                :disabled="form.processing"
                @click="submitForm"
            >
                {{ $t('public.save_changes') }}
            </Button>
        </div>
    </form>
</template>
