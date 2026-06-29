import { useChildFilterStore } from '@/spa/stores/childFilter';
import { usePersonsStore } from '@/spa/stores/persons';

/**
 * Builds the query string for the default feed.
 *
 * An empty child filter means "all photos": no person scoping is applied, so
 * posts that tag no child (such as a first moment) stay visible. A non-empty
 * filter scopes the feed to the selected children, intersected with the current
 * persons list so a stale id from a removed child cannot blank the feed.
 * "Children" = tagged persons without their own app account; linked app users
 * (other parents) are not children.
 */
export function useChildFeedQuery() {
    const personsStore = usePersonsStore();
    const childFilter = useChildFilterStore();

    async function childFeedQuery(page: number): Promise<string> {
        const params = new URLSearchParams();
        params.set('page', String(page));
        // Feeds are always ordered by capture date (taken_at), falling back to
        // upload date server-side for posts without an EXIF date.
        params.set('sort', 'taken_at');

        try {
            // The server-stored filter loads alongside the persons list so the
            // first feed build already uses the account-level selection;
            // ensureLoaded falls back to the warm cache when offline.
            const [persons] = await Promise.all([
                personsStore.ensureLoaded(),
                childFilter.ensureLoaded(),
            ]);

            const selected = childFilter.selectedIds;

            // Empty selection: leave the feed unscoped so every photo shows,
            // including posts that tag no child. Only an explicit selection
            // narrows the feed, intersected with the current children so a
            // stale id from a removed child cannot blank it.
            if (selected.length > 0) {
                const childIds = persons
                    .filter((person) => !person.user_id)
                    .map((person) => person.id);

                for (const id of childIds.filter((id) =>
                    selected.includes(id),
                )) {
                    params.append('person_ids[]', id);
                }
            }
        } catch {
            // Persons/filter unavailable: unscoped feed as a safe fallback.
        }

        return params.toString();
    }

    return { childFeedQuery };
}
