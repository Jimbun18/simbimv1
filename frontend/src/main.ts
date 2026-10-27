import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import './style.css';

import { vSplash, initGlobalSplash } from './directives/splash';
import { vReveal, initAutoScrollReveal } from './directives/reveal';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Global custom directives for animations
app.directive('splash', vSplash);
app.directive('reveal', vReveal);

app.mount('#app');

// Initialize global auto-splash on buttons and scroll reveal on elements
if (typeof window !== 'undefined') {
  initGlobalSplash();
  initAutoScrollReveal();
}

