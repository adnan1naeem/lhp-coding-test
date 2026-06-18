export interface EventVisualImage {
    url: string;
    sort_order: number;
}

export interface EventVisualLocation {
    label: string;
    display: string;
    lat: number | null;
    lng: number | null;
    city_slug: string;
    timezone: string;
}

export interface EventVisualSchedule {
    starts_at: number | null;
    ends_at: number | null;
}

export interface EventVisualItem {
    id: string;
    title: string;
    description: string;
    type: string;
    status: string;
    venue: string | null;
    location: EventVisualLocation;
    schedule: EventVisualSchedule;
    images: EventVisualImage[];
    attendee_count: number;
}

export interface CityOption {
    slug: string;
    label: string;
}

export interface EventVisualFilters {
    date_from: string | null;
    date_to: string | null;
    city: string | null;
    status: string;
}
