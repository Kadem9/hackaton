import {useEffect, useRef} from 'preact/hooks';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

type Garage = {
    name: string;
    address: string;
    city: string;
    zipcode: string;
    latitude: string;
    longitude: string;
};

type Props = {
    garages: Garage[];
};

export default function LeafletMap({ garages }: Props) {
    const mapRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!mapRef.current || garages.length === 0) return;

        L.Marker.prototype.options.icon = L.icon({
            iconUrl: '/assets/img/marker-icon.png',
            shadowUrl: '/assets/img/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        });

        const center = [
            parseFloat(garages[0].latitude),
            parseFloat(garages[0].longitude),
        ];

        // @ts-ignore
        const map = L.map(mapRef.current).setView(center, 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        garages.forEach((garage) => {
            const marker = L.marker([parseFloat(garage.latitude), parseFloat(garage.longitude)]).addTo(map);
            marker.bindPopup(`<strong>${garage.name}</strong><br>${garage.address}<br>${garage.zipcode} ${garage.city}`);
        });

        return () => map.remove();
    }, [garages]);

    return (
        <div
            ref={mapRef}
            style={{ height: '300px', width: '100%', marginBottom: '1rem', borderRadius: '10px' }}
        />
    );
}
