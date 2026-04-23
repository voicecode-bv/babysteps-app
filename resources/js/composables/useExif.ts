import exifr from 'exifr';

export interface ExifData {
    taken_at: string | null;
    latitude: number | null;
    longitude: number | null;
}

const MIN_DATE = Date.UTC(1990, 0, 1);

function clampDate(date: Date | undefined): string | null {
    if (!date || Number.isNaN(date.getTime())) {
        return null;
    }

    const t = date.getTime();
    const upper = Date.now() + 24 * 60 * 60 * 1000;

    if (t < MIN_DATE || t > upper) {
        return null;
    }

    return date.toISOString();
}

function clampCoordinate(value: unknown, max: number): number | null {
    if (typeof value !== 'number' || Number.isNaN(value)) {
        return null;
    }

    if (value < -max || value > max) {
        return null;
    }

    return value;
}

export async function readExif(source: string | Blob | File | ArrayBuffer): Promise<ExifData> {
    const empty: ExifData = { taken_at: null, latitude: null, longitude: null };

    try {
        const result = await exifr.parse(source, {
            gps: true,
            pick: ['DateTimeOriginal', 'DateTime', 'latitude', 'longitude'],
        });

        if (!result) {
            return empty;
        }

        const taken: Date | undefined = result.DateTimeOriginal ?? result.DateTime;

        return {
            taken_at: clampDate(taken),
            latitude: clampCoordinate(result.latitude, 90),
            longitude: clampCoordinate(result.longitude, 180),
        };
    } catch {
        return empty;
    }
}
