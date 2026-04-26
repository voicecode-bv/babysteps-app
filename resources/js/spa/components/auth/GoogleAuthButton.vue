<script setup lang="ts">
import { Browser } from '@nativephp/mobile';
import { computed } from 'vue';
import { useI18nStore } from '@/spa/stores/i18n';
import googleButtonEn from '../../../../svg/google-button-en.png';
import googleButtonNl from '../../../../svg/google-button-nl.png';

const props = defineProps<{
    url: string;
    label: string;
}>();

const i18n = useI18nStore();
const src = computed(() => (i18n.locale === 'nl' ? googleButtonNl : googleButtonEn));

async function go(): Promise<void> {
    await Browser.auth(props.url);
}
</script>

<template>
    <img :src="src" :alt="label" class="block h-auto w-full cursor-pointer" @click="go" />
</template>
