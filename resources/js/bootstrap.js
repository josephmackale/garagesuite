// resources/js/bootstrap.js

// If you don't need lodash, skip it completely for now.
// window._ = {};

// Axios (optional, but Breeze/Laravel often use it)
import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
