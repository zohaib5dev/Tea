import '../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { applyTheme } from './lib/theme';

// Paint the user's saved theme first, then reveal the page (see the #boot style in spa.blade.php)
applyTheme(window.__USER__?.theme);
document.getElementById('boot')?.remove();

createApp(App).use(createPinia()).use(router).mount('#app');
