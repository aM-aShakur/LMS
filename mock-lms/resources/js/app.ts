import { createApp } from 'vue';
import App from './pages/Dashboard.vue';
import router from './router';

createApp(App).use(router).mount('#app');
