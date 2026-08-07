import axios from 'axios';

const http = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

http.interceptors.response.use((response) => {
    const contentType = String(response.headers?.['content-type'] || '');

    if (contentType.includes('text/html')) {
        return Promise.reject(new Error('Expected a JSON response.'));
    }

    return response;
});

export default http;
