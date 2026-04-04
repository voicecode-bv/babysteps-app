import { HttpCancelledError, HttpNetworkError, HttpResponseError, XhrHttpClient } from '@inertiajs/core';

/**
 * Custom HTTP client that uses fetch() instead of XMLHttpRequest.
 *
 * WKWebView on iOS strips POST body data from XMLHttpRequest when using
 * custom URL schemes (php://). fetch() does not have this limitation.
 */
export class FetchHttpClient extends XhrHttpClient {
    doRequest(config: Record<string, any>): Promise<{ status: number; data: string; headers: Record<string, string> }> {
        return new Promise(async (resolve, reject) => {
            const url = this.buildUrl(config.url, config.params);

            let body: BodyInit | null = null;
            const headers: Record<string, string> = {};

            if (config.headers) {
                for (const [key, value] of Object.entries(config.headers)) {
                    headers[key] = String(value);
                }
            }

            if (config.method.toUpperCase() !== 'GET' && config.data !== null && config.data !== undefined) {
                if (config.data instanceof FormData) {
                    body = config.data;
                    // Let fetch set the Content-Type with boundary for FormData
                    delete headers['Content-Type'];
                    delete headers['content-type'];
                } else if (typeof config.data === 'object') {
                    body = JSON.stringify(config.data);
                    if (!headers['Content-Type'] && !headers['content-type']) {
                        headers['Content-Type'] = 'application/json';
                    }
                } else {
                    body = String(config.data);
                }
            }

            const xsrfToken = this.getCookie(this.xsrfCookieName);
            if (xsrfToken) {
                headers[this.xsrfHeaderName] = xsrfToken;
            }

            const controller = new AbortController();
            if (config.signal) {
                config.signal.addEventListener('abort', () => controller.abort());
            }

            try {
                const response = await fetch(url, {
                    method: config.method.toUpperCase(),
                    headers,
                    body,
                    signal: controller.signal,
                });

                const responseData = await response.text();
                const responseHeaders: Record<string, string> = {};
                response.headers.forEach((value, key) => {
                    responseHeaders[key.toLowerCase()] = value;
                });

                const result = {
                    status: response.status,
                    data: responseData,
                    headers: responseHeaders,
                };

                if (response.status >= 400) {
                    reject(new HttpResponseError(`Request failed with status ${response.status}`, result, url));
                } else {
                    resolve(result);
                }
            } catch (error) {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    reject(new HttpCancelledError('Request was cancelled', url));
                } else {
                    reject(new HttpNetworkError('Network error', url, error instanceof Error ? error : undefined));
                }
            }
        });
    }

    private buildUrl(url: string, params?: Record<string, string>): string {
        if (!params || Object.keys(params).length === 0) {
            return url;
        }

        const separator = url.includes('?') ? '&' : '?';
        const query = new URLSearchParams(params).toString();
        return `${url}${separator}${query}`;
    }

    private getCookie(name: string): string | null {
        const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
        return match ? decodeURIComponent(match[3]) : null;
    }
}
