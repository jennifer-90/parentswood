import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';


// Indique que toutes les requêtes sont AJAX
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';


// Indique à axios de chercher le cookie qui s'appelle "XSRF-TOKEN"
// (c’est Laravel qui l’émet automatiquement pour chaque utilisateur connecté)
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';

// Indique à axios d’envoyer la valeur trouvée dans ce cookie
// dans un header HTTP "X-XSRF-TOKEN" à chaque requête POST/PUT/DELETE.
// Laravel compare alors ce header avec le cookie pour vérifier que
// la requête vient bien du site et non d’un site externe (anti-CSRF).
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
