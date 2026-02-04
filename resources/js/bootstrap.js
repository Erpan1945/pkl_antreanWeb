import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Setup CSRF token for all axios requests (Laravel expects X-CSRF-TOKEN header)
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    // Helpful debug message if meta tag is missing
    console.error('CSRF token not found: please ensure <meta name="csrf-token" content="{{ csrf_token() }}"> is present in your main blade layout.');
}
