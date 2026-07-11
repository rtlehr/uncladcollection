export type SortDirection = 'asc' | 'desc';

export interface SortState {
    sort: string;
    direction: SortDirection;
}
