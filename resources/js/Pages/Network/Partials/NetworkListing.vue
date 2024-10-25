<script setup>
import {IconSearch, IconCircleXFilled, IconUserCircle, IconChevronUp, IconMinus} from "@tabler/icons-vue";
import InputText from "primevue/inputtext";
import Button from "@/Components/Button.vue";
import {ref, watch} from "vue";
import Toggleswitch from 'primevue/toggleswitch';
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";

const search = ref('');

const loading = ref(false);

const { formatAmount } = transactionFormat();



getResult();


const clearSearch = () => {
    search.value = '';
}
</script>

<template>
    <div>

    </div>
        
</template>