<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { IconDownload, IconLink } from "@tabler/icons-vue";
import Button from '@/Components/Button.vue';
import {ref, watch} from "vue";

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.download_center')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.download_center') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col items-center self-stretch bg-white p-[60px] gap-[60px] rounded-lg shadow-card">
            <div class="flex flex-col items-center gap-5 self-stretch">
                <img src="/assets/cTrader-logo.svg" alt="ctrader_logo" class="w-[246px] h-[84px]"/>
                <div class="flex flex-col gap-4 items-center self-stretch">
                    <span class="text-center text-gray-950 text-xxl font-bold">{{ $t('public.ctrader_title') }}</span>
                    <span class="text-center text-gray-700">{{ $t('public.ctrader_caption') }}</span>
                </div>
            </div>
            <div class="flex content-center items-center justify-center gap-5 self-center flex-wrap md:w-[450px] lg:w-full">
                <Button
                    type="button"
                    variant="primary-flat"
                    size="base"
                    class='items-center justify-center gap-2 md:flex hidden px-6 py-4'                 
                >
                    <IconDownload size="20" stroke-width="1.5" />
                    {{ $t('public.download_now') }}
                </Button>
                <Button
                    type="button"
                    variant="primary-tonal"
                    size="base"
                    class='items-center justify-center gap-2 md:flex hidden px-6 py-4'
                >
                    <IconLink size="20" stroke-width="1.5" />
                    {{ $t('public.web_trader') }}
                </Button>
                <Button
                    type="button"
                    size="base"
                    class='bg-black items-start justify-center gap-[10.4px] flex pl-[10.4px] pr-[10.4px] py-[8.5px] flex-shrink-0'
                >
                    <img src="/assets/apple-logo.svg" class=""/>
                    <div class="text-white flex flex-col flex-shrink-0 items-start">
                        <span>{{ $t('public.download_on_the') }}</span>
                        <span>App Store</span>
                    </div>
                </Button>
                <Button
                    type="button"
                    size="base"
                    class='bg-black items-start justify-center gap-[10.4px] flex pl-[10.4px] pr-[10.4px] py-[8.5px] flex-shrink-0'
                >
                    <img src="/assets/google-play-logo.svg" class=""/>
                    <div class="text-white flex flex-col flex-shrink-0 items-start">
                        <span>{{ $t('public.get_it_on') }}</span>
                        <span>Google Play</span>
                    </div>
                </Button>
            </div>
            <div class="flex flex-column justify-end items-center px-[50px] pt-[15px] pb-0 w-[300px]">
                <img src="/assets/phone-mockup.svg" alt="phone_mockup" class="w-[200px] h-[400px]"/>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
