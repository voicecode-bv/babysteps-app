<script setup lang="ts">
import BottomSheet from '@/components/BottomSheet.vue';
import { useTranslations } from '@/composables/useTranslations';
import { fetchComments, postComment } from '@/http/comments';
import { likeComment as likeCommentRequest, unlikeComment as unlikeCommentRequest } from '@/http/likes';
import type { Comment } from '@/types/comment';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import heartIcon from '../../svg/doodle-icons/heart.svg';
import heartFilledIcon from '../../svg/doodle-icons/heart-filled.svg';

function iconMaskStyle(url: string) {
    return {
        maskImage: `url(${url})`,
        WebkitMaskImage: `url(${url})`,
        maskSize: 'contain',
        WebkitMaskSize: 'contain',
        maskRepeat: 'no-repeat',
        WebkitMaskRepeat: 'no-repeat',
        maskPosition: 'center',
        WebkitMaskPosition: 'center',
    };
}

const props = withDefaults(
    defineProps<{
        open: boolean;
        postId: number;
        initialComments?: Comment[];
        initialReplyTo?: Comment | null;
    }>(),
    {
        initialComments: undefined,
        initialReplyTo: null,
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'commentAdded', comment: Comment): void;
}>();

const { t } = useTranslations();
const page = usePage();
const authUserId = computed(() => (page.props.auth as { user?: { id: number } })?.user?.id ?? null);

const comments = ref<Comment[]>(props.initialComments ? [...props.initialComments] : []);
const replyingTo = ref<Comment | null>(props.initialReplyTo);
const body = ref('');
const isLoading = ref(false);
const isSubmitting = ref(false);
const loadError = ref<string | null>(null);
const hasLoaded = ref(!!props.initialComments);

const commentInput = useTemplateRef<HTMLInputElement>('commentInput');
const listEnd = useTemplateRef<HTMLDivElement>('listEnd');

const totalCount = computed(() =>
    comments.value.reduce((total, comment) => total + 1 + (comment.replies?.length ?? 0), 0),
);

async function loadComments() {
    isLoading.value = true;
    loadError.value = null;
    try {
        comments.value = await fetchComments(props.postId);
        hasLoaded.value = true;
    } catch {
        loadError.value = t('Failed to load comments');
    } finally {
        isLoading.value = false;
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        if (props.initialReplyTo) {
            replyingTo.value = props.initialReplyTo;
        }

        // Focus synchronously while the user-gesture context is still alive,
        // otherwise iOS/NativePHP WebKit refuses to raise the keyboard.
        nextTick(() => {
            commentInput.value?.focus({ preventScroll: true });
        });

        if (!hasLoaded.value) {
            if (props.initialComments) {
                comments.value = [...props.initialComments];
                hasLoaded.value = true;
            } else {
                void loadComments();
            }
        }
    },
    { flush: 'sync' },
);

function close() {
    emit('update:open', false);
    replyingTo.value = null;
    body.value = '';
}

function startReply(comment: Comment) {
    replyingTo.value = comment;
    // Focus in the same tick as the tap so iOS raises the keyboard.
    commentInput.value?.focus({ preventScroll: true });
    nextTick(() => commentInput.value?.focus({ preventScroll: true }));
}

function cancelReply() {
    replyingTo.value = null;
}

function toggleCommentLike(comment: Comment) {
    if (comment.user.id === authUserId.value) return;

    const wasLiked = comment.is_liked;
    comment.is_liked = !wasLiked;
    comment.likes_count += wasLiked ? -1 : 1;

    const request = wasLiked ? unlikeCommentRequest(comment.id) : likeCommentRequest(comment.id);

    request.catch(() => {
        comment.is_liked = wasLiked;
        comment.likes_count += wasLiked ? 1 : -1;
    });
}

async function submitComment() {
    const value = body.value.trim();
    if (!value || isSubmitting.value) return;

    isSubmitting.value = true;
    try {
        const parentId = replyingTo.value?.id ?? null;
        const created = await postComment(props.postId, value, parentId);

        if (parentId) {
            const parent = comments.value.find((comment) => comment.id === parentId);
            if (parent) {
                parent.replies = [...(parent.replies ?? []), created];
            } else {
                comments.value = [...comments.value, created];
            }
        } else {
            comments.value = [...comments.value, created];
        }

        body.value = '';
        replyingTo.value = null;
        emit('commentAdded', created);

        await nextTick();
        scrollListToEnd();
        setTimeout(scrollListToEnd, 150);
    } catch {
        loadError.value = t('Failed to post comment');
    } finally {
        isSubmitting.value = false;
    }
}

function scrollListToEnd() {
    let el: HTMLElement | null = listEnd.value?.parentElement ?? null;
    while (el && getComputedStyle(el).overflowY !== 'auto') {
        el = el.parentElement;
    }
    if (el) {
        el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
    }
}

function timeAgo(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) return t('just now');
    if (seconds < 3600) return t(':count min ago', { count: Math.floor(seconds / 60) });
    if (seconds < 86400) return t(':count hours ago', { count: Math.floor(seconds / 3600) });
    if (seconds < 604800) return t(':count days ago', { count: Math.floor(seconds / 86400) });
    return t(':count weeks ago', { count: Math.floor(seconds / 604800) });
}

function onSheetUpdate(value: boolean) {
    if (!value) {
        close();
    } else {
        emit('update:open', true);
    }
}
</script>

<template>
    <BottomSheet :open="open" @update:open="onSheetUpdate">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-sand-700 dark:text-sand-300">
                    {{ t('Comments') }}
                    <span v-if="totalCount > 0" class="font-normal text-sand-400 dark:text-sand-500">({{ totalCount }})</span>
                </h2>
                <button class="text-sand-500 dark:text-sand-400" :aria-label="t('Close')" @click="close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>

        <div v-if="isLoading" class="flex items-center justify-center px-4 py-10">
            <svg class="size-6 animate-spin text-sand-400 dark:text-sand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
        </div>

        <div v-else-if="loadError" class="px-4 py-10 text-center">
            <p class="text-sm text-blush-500">{{ loadError }}</p>
            <button class="mt-2 text-xs font-medium text-sand-500 dark:text-sand-400" @click="loadComments">{{ t('Try again') }}</button>
        </div>

        <div v-else-if="comments.length === 0" class="px-4 py-10 text-center">
            <p class="text-sm font-medium text-sand-600 dark:text-sand-300">{{ t('No comments yet') }}</p>
            <p class="mt-1 text-sm text-sand-400 dark:text-sand-500">{{ t('Share what you think!') }}</p>
        </div>

        <div v-else>
            <div v-for="comment in comments" :key="comment.id">
                <div class="flex gap-3 border-b border-sand-50 px-4 py-3 dark:border-sand-800">
                    <Link :href="`/profiles/${comment.user.username}`" class="mt-0.5 flex-shrink-0">
                        <img
                            :src="comment.user.avatar ?? `https://ui-avatars.com/api/?name=${comment.user.name}&background=f0dcc6&color=5c3f24&size=64`"
                            :alt="comment.user.name"
                            class="size-8 rounded-full object-cover"
                        />
                    </Link>
                    <div class="flex-1">
                        <p class="text-sm text-sand-800 dark:text-sand-200">
                            <Link :href="`/profiles/${comment.user.username}`" class="font-semibold">{{ comment.user.name }}</Link>
                            {{ ' ' + comment.body }}
                        </p>
                        <div class="mt-1 flex items-center gap-3">
                            <span class="text-xs text-sand-400 dark:text-sand-500">{{ timeAgo(comment.created_at) }}</span>
                            <button class="text-xs font-medium text-sand-500 dark:text-sand-400" @click="startReply(comment)">{{ t('Reply') }}</button>
                        </div>
                    </div>
                    <div class="mt-1 flex flex-shrink-0 flex-col items-center gap-0.5">
                        <button :disabled="comment.user.id === authUserId" @click="toggleCommentLike(comment)">
                            <span
                                aria-hidden="true"
                                class="inline-block size-4"
                                :class="comment.is_liked ? 'bg-blush-400' : 'bg-sand-400 dark:bg-sand-500'"
                                :style="iconMaskStyle(comment.is_liked ? heartFilledIcon : heartIcon)"
                            ></span>
                        </button>
                        <span v-if="comment.likes_count > 0" class="text-[10px] text-sand-400 dark:text-sand-500">{{ comment.likes_count }}</span>
                    </div>
                </div>

                <div v-for="reply in (comment.replies ?? [])" :key="reply.id" class="flex gap-3 border-b border-sand-50 py-3 pl-14 pr-4 dark:border-sand-800">
                    <Link :href="`/profiles/${reply.user.username}`" class="mt-0.5 flex-shrink-0">
                        <img
                            :src="reply.user.avatar ?? `https://ui-avatars.com/api/?name=${reply.user.name}&background=f0dcc6&color=5c3f24&size=64`"
                            :alt="reply.user.name"
                            class="size-6 rounded-full object-cover"
                        />
                    </Link>
                    <div class="flex-1">
                        <p class="text-sm text-sand-800 dark:text-sand-200">
                            <Link :href="`/profiles/${reply.user.username}`" class="font-semibold">{{ reply.user.name }}</Link>
                            {{ ' ' + reply.body }}
                        </p>
                        <div class="mt-1 flex items-center gap-3">
                            <span class="text-xs text-sand-400 dark:text-sand-500">{{ timeAgo(reply.created_at) }}</span>
                            <button class="text-xs font-medium text-sand-500 dark:text-sand-400" @click="startReply(comment)">{{ t('Reply') }}</button>
                        </div>
                    </div>
                    <div class="mt-1 flex flex-shrink-0 flex-col items-center gap-0.5">
                        <button :disabled="reply.user.id === authUserId" @click="toggleCommentLike(reply)">
                            <span
                                aria-hidden="true"
                                class="inline-block size-4"
                                :class="reply.is_liked ? 'bg-blush-400' : 'bg-sand-400 dark:bg-sand-500'"
                                :style="iconMaskStyle(reply.is_liked ? heartFilledIcon : heartIcon)"
                            ></span>
                        </button>
                        <span v-if="reply.likes_count > 0" class="text-[10px] text-sand-400 dark:text-sand-500">{{ reply.likes_count }}</span>
                    </div>
                </div>
            </div>
            <div ref="listEnd" />
        </div>

        <template #footer>
            <div v-if="replyingTo" class="flex items-center justify-between border-b border-sand-100 px-4 py-2 dark:border-sand-800">
                <span class="text-xs text-sand-500 dark:text-sand-400">
                    {{ t('Replying to') }} <span class="font-semibold">{{ replyingTo.user.name }}</span>
                </span>
                <button class="text-sand-500 dark:text-sand-400" :aria-label="t('Cancel reply')" @click="cancelReply">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex items-center gap-3 px-4 py-3">
                <div class="size-8 flex-shrink-0 rounded-full bg-sand-200 dark:bg-sand-800" />
                <form class="flex flex-1 items-center gap-2" @submit.prevent="submitComment">
                    <input
                        ref="commentInput"
                        v-model="body"
                        type="text"
                        inputmode="text"
                        autocapitalize="sentences"
                        enterkeyhint="send"
                        :placeholder="replyingTo ? t('Write a reply...') : t('Write a comment...')"
                        class="flex-1 bg-transparent text-sm text-sand-800 placeholder-sand-400 focus:outline-none dark:text-sand-100 dark:placeholder-sand-500"
                        @click.stop
                    />
                    <button
                        type="submit"
                        class="text-sm font-semibold text-sand-600 disabled:opacity-30 dark:text-sand-400"
                        :disabled="!body.trim() || isSubmitting"
                    >
                        {{ t('Post') }}
                    </button>
                </form>
            </div>
        </template>
    </BottomSheet>
</template>
