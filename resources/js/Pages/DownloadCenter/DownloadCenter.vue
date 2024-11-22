<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { 
    Apple,
    PlayStore,
    GooglePlay,
} from '@/Components/Icons/solid.jsx';
import { 
    IconDownload, 
    IconLink, 
} from "@tabler/icons-vue";
import Button from '@/Components/Button.vue';
import { computed, ref, h, watchEffect } from "vue";
import toast from "@/Composables/toast.js";

const getDownloadLink = () => {
    const userAgent = navigator.userAgent.toLowerCase();

    if (userAgent.includes('win')) {
        return 'https://getctrader.com/quantumcapitalglobal/ctrader-quantumcapitalglobal-setup.exe'; // Windows version
    } else if (userAgent.includes('mac')) {
        return 'https://getctradermac.com/quantumcapitalglobal/ctrader-quantumcapitalglobal-setup.dmg'; // Mac version
    } else {
        return null;
    }
};

const downloadApp = () => {
    const link = getDownloadLink();
    if (link) {
        window.location.href = link;
    } else {
        toast.add({
                title: trans('public.unsupported_operating_system'),
                type: 'warning',
            });
    }
};

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
                <Button type="button" size="lg" variant="primary-flat" class="hidden md:flex" @click="downloadApp">
                    <IconDownload size="20" stroke-width="1.5" />
                    <span class="text-white text-center text-sm font-medium">{{ $t('public.download_now') }}</span>
                </Button>
                <a href="https://id-app.qcgexchange.com/" class="hidden md:flex">
                    <Button type="button" size="lg" variant="primary-tonal">
                        <IconLink size="20" stroke-width="1.5" />
                        <span class="text-primary-600 text-center text-sm font-medium">{{ $t('public.web_trader') }}</span>
                    </Button>
                </a>
                <a href="https://apps.apple.com/my/app/ctrader/id767428811">
                    <div class="w-[156px] h-[52px] bg-black items-start justify-center gap-[10.4px] flex pl-[10.4px] pr-[7.8px] py-[8.5px] flex-shrink-0 rounded-[7.091px] cursor-pointer hover:bg-gray-700">
                        <Apple class="w-[26px] h-[31.20px] flex-shrink-0" />
                        <div class="w-[101.4px] flex flex-col items-start flex-shrink-0">
                            <span class="self-stretch text-white text-[11.7px] leading-none font-medium">{{ $t('public.download_on_the') }}</span>
                            <span class="self-stretch text-white text-[23.4px] leading-none font-medium tracking-[-0.611px]">{{ $t('public.app_store') }}</span>
                        </div>
                    </div>
                </a>
                <a href="https://play.google.com/store/apps/details?id=com.spotware.ct&hl=en">
                    <div class="w-[156px] h-[52px] bg-black items-end justify-center gap-[9.1px] flex pl-[10.4px] pr-[13px] pt-[6.5px] pb-[10.1px] flex-shrink-0 rounded-[7.091px] cursor-pointer hover:bg-gray-700">
                        <PlayStore class="w-[27.3px] h-[31.2px] flex-shrink-0" />
                        <div class="flex flex-col items-start gap-[3.9px]">
                            <span class="leading-none self-center text-white text-[11px] uppercase font-light">{{ $t('public.get_it_on') }}</span>
                            <GooglePlay class="w-[96.2px] h-[19.5px] fill-white" />
                        </div>
                    </div>
                </a>
            </div>
            <div class="flex flex-column justify-end items-center px-[50px] pt-[15px] pb-0 w-[300px]">
                <img src="/assets/phone-mockup.svg" alt="phone_mockup" class="w-[200px] h-[400px]"/>
            </div>
        </div>

    </AuthenticatedLayout>
</template>