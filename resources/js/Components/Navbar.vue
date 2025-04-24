<script setup>
import { sidebarState } from '@/Composables'
import {
    IconLanguage,
    IconTransferOut,
    IconMenu2,
    IconQrcode,
    IconCopy,
    IconMessage
} from '@tabler/icons-vue';
import QrcodeVue from 'qrcode.vue'
import Dialog from "primevue/dialog";
import ProfilePhoto from "@/Components/ProfilePhoto.vue";
import {Link, usePage} from "@inertiajs/vue3";
import TieredMenu from "primevue/tieredmenu";
import {ref} from "vue";
import Button from "@/Components/Button.vue";
import {loadLanguageAsync} from "laravel-vue-i18n";
import Tag from "primevue/tag";
import InputText from "primevue/inputtext";
import { ForumIcon, ForumNotifIcon } from '@/Components/Icons/outline'

defineProps({
    title: String
})

const tooltipText = ref('copy')
const visible = ref(false);
const qrcodeContainer = ref();
const registerLink = ref(`${window.location.origin}/sign_up/${usePage().props.auth.user.referral_code}`);

const menu = ref(null);
const toggle = (event) => {
    menu.value.toggle(event);
};

const currentLocale = ref(usePage().props.locale);
const locales = [
    {'label': 'English', 'value': 'en'},
    {'label': '中文(繁体)', 'value': 'tw'},
    {'label': '中文(简体)', 'value': 'cn'},
];

const changeLanguage = async (langVal) => {
    try {
        currentLocale.value = langVal;
        await loadLanguageAsync(langVal);
        await axios.get(`/locale/${langVal}`);
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

const copyToClipboard = (text) => {
    const textToCopy = text;

    const textArea = document.createElement('textarea');
    document.body.appendChild(textArea);

    textArea.value = textToCopy;
    textArea.select();

    try {
        const successful = document.execCommand('copy');

        tooltipText.value = 'copied';
        setTimeout(() => {
            tooltipText.value = 'copy';
        }, 1500);
    } catch (err) {
        console.error('Copy to clipboard failed:', err);
    }

    document.body.removeChild(textArea);
}

const downloadQrCode = () => {
    const canvas = qrcodeContainer.value.querySelector("canvas");
    const link = document.createElement("a");
    link.download = "qr-code.png";
    link.href = canvas.toDataURL("image/png");
    link.click();
}
</script>

<template>
    <nav
        aria-label="secondary"
        class="sticky top-0 z-10 py-2 px-2 md:px-5 bg-white flex items-center gap-2 md:gap-3"
    >
        <div
            class="inline-flex justify-center items-center rounded-full hover:bg-gray-100 w-12 h-12 shrink-0 grow-0 hover:select-none hover:cursor-pointer"
            @click="sidebarState.isOpen = !sidebarState.isOpen"
        >
            <IconMenu2 size="20" color="#182230" stroke-width="1.25" />
        </div>
        <div class="w-full h-full flex items-center">
            <Link class="h-full flex items-center gap-2"
                :href="route('dashboard')"
            >
                <img src="/assets/logo.svg" alt="no data" class="w-7 h-7" />
                <div class="hidden md:flex flex-col items-start">
                    <span class="text-gray-950 text-sm font-bold">Quantum</span>
                    <span class="text-gray-700 text-xxxs font-medium tracking-[1.04px]">Capital Group</span>
                </div>
            </Link>
        </div>
        <!-- <div
            class="text-base md:text-lg font-semibold text-gray-950 w-full"
        >
            {{ title }}
        </div> -->
        <div class="flex items-center">
            <Link
                class="w-9 h-9 md:w-12 md:h-12 p-3.5 flex items-center justify-center rounded-full outline-none hover:cursor-pointer hover:bg-gray-100 text-gray-700 focus:bg-gray-100"
                :href="route('forum')"
                method="get"
                as="button"
            >
                <ForumIcon aria-hidden="true" class="flex-shrink-0 w-5 h-5" />
                <!-- <ForumNotifIcon aria-hidden="true" class="flex-shrink-0 w-5 h-5" /> -->
            </Link>
            <div
                class="w-9 h-9 md:w-12 md:h-12 p-3.5 flex items-center justify-center rounded-full hover:cursor-pointer hover:bg-gray-100 text-gray-700 focus:bg-gray-100"
                @click="toggle"
            >
                <IconLanguage size="20" stroke-width="1.25" class="flex-shrink-0 w-5 h-5"/>
            </div>
            <Button
                variant="gray-text"
                class="w-9 h-9 md:w-12 md:h-12 p-3.5 !ring-0"
                size="base"
                type="button"
                iconOnly
                pill
                @click="visible = true"
            >
                <IconQrcode size="20" stroke-width="1.5" class="flex-shrink-0 w-5 h-5"/>
            </Button>
            <Link
                class="w-9 h-9 md:w-12 md:h-12 p-3.5 flex items-center justify-center rounded-full outline-none hover:cursor-pointer hover:bg-gray-100 text-gray-700 focus:bg-gray-100"
                :href="route('logout')"
                method="post"
                as="button"
            >
                <IconTransferOut size="20" stroke-width="1.25" class="flex-shrink-0 w-5 h-5"/>
            </Link>
        </div>
    </nav>

    <TieredMenu ref="menu" id="overlay_tmenu" :model="locales" popup>
        <template #item="{ item, props }">
            <div
                class="flex items-center gap-3 self-stretch text-gray-700"
                :class="{'bg-primary-100 text-primary-600': item.value === currentLocale}"
                v-bind="props.action"
                @click="changeLanguage(item.value)"
            >
                {{ item.label }}
            </div>
        </template>
    </TieredMenu>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.referral_qr_code')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col pt-4 md:pt-6 gap-6 md:gap-8 items-center self-stretch">
            <span class="text-xs md:text-base text-gray-500">{{ $t('public.referral_qr_caption_1') }}</span>

            <!-- qr code -->
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div
                    ref="qrcodeContainer">
                    <qrcode-vue
                        ref="qrcode"
                        :value="registerLink"
                        :margin="2"
                        :size="200"
                    />
                </div>
                <Button
                    type="button"
                    variant="primary-flat"
                    size="lg"
                    @click="downloadQrCode"
                >
                    {{ $t('public.download_qr_caption') }}
                </Button>
            </div>

            <div class="flex gap-3 items-center self-stretch">
                <div class="h-[1px] bg-gray-200 rounded-[5px] w-full"></div>
                <div class="text-xs md:text-sm text-gray-500 text-center min-w-[145px] md:w-full">{{ $t('public.referral_qr_caption_2') }}</div>
                <div class="h-[1px] bg-gray-200 rounded-[5px] w-full"></div>
            </div>

            <div class="flex gap-3 items-center self-stretch relative">
                <InputText
                    v-model="registerLink"
                    class="truncate w-full"
                    readonly
                />
                <Tag
                    v-if="tooltipText === 'copied'"
                    class="absolute -top-7 -right-3"
                    severity="contrast"
                    :value="$t(`public.${tooltipText}`)"
                ></Tag>
                <Button
                    type="button"
                    variant="gray-text"
                    iconOnly
                    pill
                    @click="copyToClipboard(registerLink)"
                >
                    <IconCopy size="20" color="#667085" stroke-width="1.25" />
                </Button>
            </div>
        </div>
    </Dialog>
</template>
