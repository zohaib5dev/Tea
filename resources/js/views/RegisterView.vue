<script setup>
import { reactive, ref } from 'vue';
import http, { errorText } from '@/lib/http';

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const busy = ref(false);
const error = ref('');
const appName = window.__APP_NAME__ || 'App';

async function submit() {
    busy.value = true; error.value = '';
    try {
        await http.post('/register', form);
        window.location.assign('/feed');
    } catch (e) {
        error.value = errorText(e);
        busy.value = false;
    }
}
</script>

<template>
    <div class="grid min-h-screen place-items-center px-4 py-10">
        <div class="card w-full max-w-md p-8">
            <h1 class="bg-gold-grad bg-clip-text text-center font-display text-5xl font-bold text-transparent">{{ appName }}</h1>
            <p class="mt-2 text-center text-ink-300">Create your account.</p>

            <form class="mt-8 space-y-4" @submit.prevent="submit">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-semibold text-ink-200">Name</label>
                    <input id="name" v-model="form.name" required maxlength="255" autocomplete="name" class="input">
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-ink-200">Email</label>
                    <input id="email" v-model="form.email" type="email" required autocomplete="email" class="input">
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-ink-200">Password</label>
                    <input id="password" v-model="form.password" type="password" required minlength="8" autocomplete="new-password" class="input">
                </div>
                <div>
                    <label for="password2" class="mb-1.5 block text-sm font-semibold text-ink-200">Confirm password</label>
                    <input id="password2" v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="input">
                </div>
                <p v-if="error" class="text-sm text-red-300" role="alert">{{ error }}</p>
                <button class="btn-gold w-full" :disabled="busy">{{ busy ? 'Creating…' : 'Create account' }}</button>
            </form>

            <p class="mt-6 text-center text-sm text-ink-300">
                Already have an account? <RouterLink to="/login" class="font-semibold text-gold-300 hover:underline">Log in</RouterLink>
            </p>
        </div>
    </div>
</template>
