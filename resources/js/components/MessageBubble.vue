<script setup>
import { computed } from 'vue';
import { longDuration, timeOfDay } from '@/lib/format';
import AppIcon from '@/components/AppIcon.vue';

const props = defineProps({ m: Object, meId: Number, isGroup: Boolean });
const emit = defineEmits(['reply', 'delete', 'callback']);

const mine = computed(() => props.m.user_id === props.meId);
const isCall = computed(() => props.m.type === 'call');
const video = computed(() => props.m.call_kind === 'video');
const fileUrl = computed(() => props.m.attachment_path ? '/storage/' + props.m.attachment_path : null);

// ----- Messenger-style call card -----
const call = computed(() => {
    if (!isCall.value) return null;
    const kind = video.value ? 'Video call' : 'Voice call';
    const iAmCaller = mine.value;
    const status = props.m.call_status;

    let title = kind, sub = '', bad = false;
    if (status === 'ended') { title = kind + ' ended'; sub = props.m.call_duration ? longDuration(props.m.call_duration) : ''; }
    else if (status === 'missed') { bad = !iAmCaller; title = iAmCaller ? 'No answer' : 'Missed ' + kind.toLowerCase(); }
    else if (status === 'declined') { title = iAmCaller ? 'Call declined' : 'You declined the call'; }
    else if (status === 'ongoing') { sub = 'In progress'; }
    else { title = iAmCaller ? 'Calling…' : 'Incoming ' + kind.toLowerCase(); }

    return { title, sub, bad, canCallBack: ['ended', 'missed', 'declined'].includes(status) };
});
</script>

<template>
    <!-- call card -->
    <div v-if="isCall" class="flex justify-center">
        <div class="flex items-center gap-3 rounded-2xl border border-ink-700 bg-ink-800/60 px-4 py-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full" :class="call.bad ? 'bg-red-500/20 text-red-300' : 'bg-gold-grad text-ink-950'">
                <AppIcon :name="video ? 'video' : 'phone'" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <p class="text-sm font-bold" :class="call.bad ? 'text-red-300' : ''">{{ call.title }}</p>
                <p class="text-xs text-ink-300">{{ call.sub ? call.sub + ', ' : '' }}{{ timeOfDay(m.created_at) }}</p>
            </div>
            <button v-if="call.canCallBack" type="button" class="btn-ghost ml-2 !px-3 !py-1.5 !text-xs" @click="emit('callback', video)">Call back</button>
        </div>
    </div>

    <!-- normal message -->
    <div v-else class="flex" :class="mine ? 'justify-end' : 'justify-start'">
        <div class="max-w-[80%]">
            <p v-if="!mine && isGroup" class="mb-0.5 px-2 text-xs font-semibold text-gold-400">{{ m.user?.name }}</p>

            <div class="rounded-2xl px-4 py-2.5 text-sm" :class="mine ? 'rounded-br-md bg-gold-grad text-ink-950' : 'rounded-bl-md bg-ink-800 text-ink-50'">
                <div v-if="m.reply_to" class="mb-2 truncate rounded-lg border-l-2 px-2 py-1 text-xs"
                     :class="mine ? 'border-ink-950/50 bg-ink-950/10' : 'border-gold-500 bg-ink-900'">{{ m.reply_to.body || 'Attachment' }}</div>

                <template v-if="fileUrl">
                    <a v-if="m.attachment_type === 'image'" :href="fileUrl" target="_blank" rel="noopener"><img :src="fileUrl" alt="Shared image" class="mb-1 max-h-64 rounded-xl"></a>
                    <a v-else :href="fileUrl" target="_blank" rel="noopener" class="mb-1 block underline">Open attachment</a>
                </template>

                <p v-if="m.body" class="whitespace-pre-wrap break-words">{{ m.body }}</p>
            </div>

            <div class="mt-1 flex items-center gap-3 px-2 text-[11px] text-ink-300" :class="mine ? 'justify-end' : ''">
                <span>{{ timeOfDay(m.created_at) }}</span>
                <button type="button" class="font-semibold hover:text-gold-300" @click="emit('reply', m)">Reply</button>
                <button type="button" class="font-semibold hover:text-red-400" @click="emit('delete', m)">Delete</button>
            </div>
        </div>
    </div>
</template>
