<script setup>
import { reactive, ref } from "vue";
import http, { errorText } from "@/lib/http";

const form = reactive({ email: "", password: "", remember: false });
const busy = ref(false);
const error = ref(
  new URLSearchParams(location.search).has("banned")
    ? "Your account has been suspended."
    : ""
);
const appName = window.__APP_NAME__ || "App";

async function submit() {
  busy.value = true;
  error.value = "";
  try {
    await http.post("/login", form);
    window.location.assign("/feed"); // full load: fresh session, CSRF token and real-time connection
  } catch (e) {
    error.value = errorText(e);
    busy.value = false;
  }
}
</script>

<template>
  <div class="grid min-h-screen place-items-center px-4">
    <div class="card w-full max-w-md p-8">
      <div class="mx-auto flex max-w-6xl items-center justify-center gap-4 px-4 py-3">
        <RouterLink to="/feed" class="flex items-center gap-3">
          <img src="/logo.webp" alt="Logo" class="h-15 w-20 object-contain" />
          <h1
            class="bg-gold-grad bg-clip-text font-display text-5xl font-bold leading-none text-transparent whitespace-nowrap"
          >
            {{ appName }}
          </h1>
        </RouterLink>
      </div>
      <p class="mt-2 text-center text-ink-300">Welcome back. Log in to continue.</p>

      <form class="mt-8 space-y-4" @submit.prevent="submit">
        <div>
          <label for="email" class="mb-1.5 block text-sm font-semibold text-ink-200"
            >Email</label
          >
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="input"
          />
        </div>
        <div>
          <label for="password" class="mb-1.5 block text-sm font-semibold text-ink-200"
            >Password</label
          >
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            class="input"
          />
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-200">
          <input
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 accent-[#d4a63a]"
          />
          Remember me
        </label>
        <p v-if="error" class="text-sm text-red-300" role="alert">{{ error }}</p>
        <button class="btn-gold w-full" :disabled="busy">
          {{ busy ? "Logging in…" : "Log in" }}
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-ink-300">
        New here?
        <RouterLink to="/register" class="font-semibold text-gold-300 hover:underline"
          >Create an account</RouterLink
        >
      </p>
    </div>
  </div>
</template>
