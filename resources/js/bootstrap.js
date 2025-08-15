import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';




// Indique que toutes les requêtes sont AJAX
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Récupère le token CSRF depuis la balise <meta>
const meta = document.querySelector('meta[name="csrf-token"]');
const token = meta ? meta.getAttribute('content') : null;

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
} else {
    console.warn('CSRF token not found: please ensure <meta name="csrf-token" ...> is in your Blade layout.');
}
