import './bootstrap';
import './api';
import './utils';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';

// Alpine plugins
Alpine.plugin(focus);
Alpine.plugin(collapse);

// Make Alpine and Chart available globally
window.Alpine = Alpine;
window.Chart = Chart;

// Start Alpine
Alpine.start();
