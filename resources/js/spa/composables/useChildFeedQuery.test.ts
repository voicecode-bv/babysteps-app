import { beforeEach, expect, it, vi } from 'vitest';
import { useChildFeedQuery } from './useChildFeedQuery';

const persons = vi.fn();
const filterIds = vi.fn();

vi.mock('@/spa/stores/persons', () => ({
    usePersonsStore: () => ({
        ensureLoaded: () => Promise.resolve(persons()),
    }),
}));

vi.mock('@/spa/stores/childFilter', () => ({
    useChildFilterStore: () => ({
        ensureLoaded: () => Promise.resolve(),
        get selectedIds() {
            return filterIds();
        },
    }),
}));

beforeEach(() => {
    persons.mockReset();
    filterIds.mockReset();
    persons.mockReturnValue([
        { id: 'kid-1', user_id: null },
        { id: 'kid-2', user_id: null },
        { id: 'parent-1', user_id: 'u-1' },
    ]);
});

function personIds(query: string): string[] {
    return new URLSearchParams(query).getAll('person_ids[]');
}

it('applies no person scoping when the filter is empty (all photos)', async () => {
    filterIds.mockReturnValue([]);

    const { childFeedQuery } = useChildFeedQuery();
    const query = await childFeedQuery(1);

    expect(personIds(query)).toEqual([]);
    expect(new URLSearchParams(query).get('sort')).toBe('taken_at');
});

it('scopes the feed to the selected children', async () => {
    filterIds.mockReturnValue(['kid-2']);

    const { childFeedQuery } = useChildFeedQuery();
    const query = await childFeedQuery(1);

    expect(personIds(query)).toEqual(['kid-2']);
});

it('drops stale selected ids that are no longer children', async () => {
    filterIds.mockReturnValue(['kid-1', 'removed-kid']);

    const { childFeedQuery } = useChildFeedQuery();
    const query = await childFeedQuery(1);

    expect(personIds(query)).toEqual(['kid-1']);
});

it('never scopes to a linked parent account', async () => {
    filterIds.mockReturnValue(['parent-1']);

    const { childFeedQuery } = useChildFeedQuery();
    const query = await childFeedQuery(1);

    // parent-1 has a user_id, so it is not a child and cannot scope the feed;
    // with no resolvable child the feed stays unscoped (all photos).
    expect(personIds(query)).toEqual([]);
});
