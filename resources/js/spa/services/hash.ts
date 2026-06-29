/**
 * SHA-256 hex digest. Used to stitch a first-party user id into attribution
 * without ever handing the raw identifier to the SDK or the ad networks.
 */
export async function sha256Hex(value: string): Promise<string> {
    const bytes = new TextEncoder().encode(value);
    const digest = await crypto.subtle.digest('SHA-256', bytes);

    return Array.from(new Uint8Array(digest))
        .map((byte) => byte.toString(16).padStart(2, '0'))
        .join('');
}
