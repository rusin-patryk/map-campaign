<template>
    <div class="mapbox-container">
        <l-map
            ref="map"
            :zoom="zoom"
            :min-zoom="6"
            :center="center"
            style="height: 600px; width: 100vw"
        >
            <l-tile-layer :url="getTilesUrl" :attribution="attribution" :options="{tileSize: 512, zoomOffset: -1}" />
            <v-marker-cluster :options="{spiderfyOnMaxZoom: true, disableClusteringAtZoom: 13, maxClusterRadius: 60}">
                <l-marker
                    v-for="(marker, index) in markers"
                    :key="index"
                    :lat-lng="marker"
                >
                    <l-popup>
                        <div class="marker-name">
                            <strong>{{ marker.name }}</strong>
                        </div>
                        <div class="marker-address">
                            {{ marker.address }}<br>
                            {{ marker.postalCode }} {{ marker.city }}
                        </div>
                        <div class="marker-phone">
                            tel.: <a :href="`tel:${marker.phone}`">{{ marker.phone }}</a>
                        </div>
                        <a class="marker-nav"
                           :href="`https://www.google.com/maps/dir//${marker.lat},${marker.lng}`"
                           target="_blank">Nawiguj</a>
                    </l-popup>
                </l-marker>
            </v-marker-cluster>
            <l-marker v-if="pickedLocation.length === 2" :lat-lng="pickedLocation">
                <l-popup>
                    <div class="marker-name">
                        <strong>Znaleziona lokalizacja</strong>
                    </div>
                </l-popup>
                <l-icon :icon-size="[64, 64]" :icon-anchor="[32, 60]" className="picked-location">
                    <img alt="Jesteś tu" title="Znaleziona lokalizacja" src="./../assets/marker-icon-big.png">
                </l-icon>
            </l-marker>
            <l-geo-json :geojson="geojson" :visible="geojsonVisible"></l-geo-json>
        </l-map>
        <hr>
        <button type="button" @click="getUserLocation">Moja lokalizacja</button>
        lub
        <input v-model="formData.postalCode" placeholder="XX-XXX" v-mask="'##-###'" />
        lub
        <input v-model="formData.city" placeholder="Miasto" />
        ->
        <button type="button" @click="searchLocation(formData.postalCode, formData.city)">Szukaj</button>
        <hr>
        <LocationPicker :locations="locations" @pickLocation="centerOnLocation" @close="closePicker" />
    </div>
</template>

<script>
import { geoJson, latLngBounds } from 'leaflet';
import { LIcon, LMap, LMarker, LPopup, LTileLayer, LGeoJson } from 'vue2-leaflet';
import LocationPicker from '@/components/LocationPicker';
import leafletKnn from 'leaflet-knn';
import Vue2LeafletMarkerCluster from 'vue2-leaflet-markercluster';
import { markers } from '@/assets/markers';
import { mask } from 'vue-the-mask';
import axios from 'axios';

export default {
    name: 'MapBox',
    directives: {mask},
    components: {
        LMap,
        LTileLayer,
        LMarker,
        LPopup,
        LIcon,
        LGeoJson,
        VMarkerCluster: Vue2LeafletMarkerCluster,
        LocationPicker
    },
    props: {
        msg: String,
    },
    data() {
        return {
            zoom: 6,
            formData: {
                postalCode: '',
                city: '',
            },
            center: [52.1302243, 19.3478346],
            pickedLocation: [],
            url: 'https://api.mapbox.com/styles/v1/mapbox/streets-v8/tiles/{z}/{x}/{y}?',
            token: 'access_token=pk.eyJ1IjoibmFub2l0IiwiYSI6ImNrcnZ2cW42ejBhZXQybm44ZXdnenRzbGsifQ.29MhI2aCgJMB93atv6eGtQ',
            markers: markers,
            locations: [],
            attribution: '© <a href="https://apps.mapbox.com/feedback/" target="_blank">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
            geojson: null,
            geojsonVisible: false,
        };
    },

    computed: {
        getTilesUrl() {
            return `${this.url}${this.token}`
        },

        getGeoJSONData() {
            const points = [];
            markers.forEach((element) => {
                points.push({
                    type: "Point",
                    coordinates: [element.lat, element.lng],
                    properties: {
                        name: `${element.name} - ${element.address}, ${element.postalCode} ${element.city}`,
                    },
                });
            });

            return geoJson(points);
        }
    },

    mounted() {
        setTimeout(() => {
            document.querySelector('a[href="https://leafletjs.com"]').setAttribute('target', '_blank');
        })
    },

    methods: {
        searchLocation(postalCode, city) {
            if (postalCode || city) {
                let requestUrl = 'https://api.mapbox.com/geocoding/v5/mapbox.places/';
                if (postalCode) {
                    requestUrl += postalCode.replace(' ', '') + '.json?country=PL&types=postcode&limit=10&' + this.token;
                } else if (city) {
                    requestUrl += city.replace(' ', '%20') + '.json?country=PL&language=pl&&types=place&limit=10&' + this.token;
                }
                this.pickedLocation = [];
                axios.get(requestUrl)
                    .then(response => {
                        this.locations = response.data.features;
                        if (this.locations.length === 1) {
                            this.centerOnLocation(this.locations[0]);
                        }
                    });
            }
        },

        getUserLocation() {
            navigator.geolocation.getCurrentPosition((location) => {
                console.log(location);
                this.getAddress([location.coords.longitude, location.coords.latitude]);
            }, () => {
                alert('Błąd! Nie udało się pobrać lokalizacji.');
            }, {enableHighAccuracy: true});
        },

        centerOnLocation(location) {
            this.emitLocation(location);
            const latLng = [location.center[1], location.center[0]];
            this.pickedLocation = latLng;
            const closest = leafletKnn(this.getGeoJSONData).nearest(latLng, 2);
            let bounds = null;
            if (location.place_type[0] === 'postcode' || location.place_type[0] === 'geolocation') {
                bounds = latLngBounds(latLng, this.getKnnLatLng(closest[0]));
            } else {
                bounds = latLngBounds([latLng, this.getKnnLatLng(closest[0]), this.getKnnLatLng(closest[1])]);
            }
            this.$refs.map.mapObject.flyToBounds(bounds, {padding: [60, 60], maxZoom: location.place_type[0] === 'postcode' ? 15 : 14, animate: false});
            this.searchRoute(latLng,  closest[0]);
            this.locations = [];
        },

        searchRoute(latLng, closest) {
            const requestUrl = `https://api.mapbox.com/directions/v5/mapbox/driving/${latLng[1].toFixed(4)},${latLng[0].toFixed(4)};${this.getKnnLatLng(closest)[1]},${this.getKnnLatLng(closest)[0]}?geometries=geojson&access_token=pk.eyJ1IjoibmFub2l0IiwiYSI6ImNrcnZ2cW42ejBhZXQybm44ZXdnenRzbGsifQ.29MhI2aCgJMB93atv6eGtQ`;
            this.geojsonVisible = false;
            axios.get(requestUrl)
                .then(response => {
                    this.geojson = response.data.routes[0].geometry;
                    this.geojsonVisible = true;
                    this.emitRoute(response.data.routes[0], closest);
                });
        },

        getAddress(location) {
            let requestUrl = `https://api.mapbox.com/geocoding/v5/mapbox.places/${location[0].toFixed(4)},${location[1].toFixed(4)}.json?${this.token}`;
            axios.get(requestUrl)
                .then(response => {
                    console.log(response.data);
                    this.centerOnLocation({
                        center: location,
                        place_type: ['geolocation'],
                        place_name: response.data.features[0].place_name,
                    });
                });
        },

        emitLocation(location) {
            let placeType = '';
            if (location.place_type[0] === 'postcode') {
                placeType = ' po kodzie pocztowym';
            } else if (location.place_type[0] === 'place') {
                placeType = ' po nazwie miejscowości';
            }
            this.$emit('pickedLocation', `Znaleziona lokalizacja${placeType}: ${location.place_name_pl ? location.place_name_pl : location.place_name}`);
        },

        emitRoute(route, closest) {
            this.$emit('closestPoint', {name: `Najbliższy sklep to ${closest.layer.feature.geometry.properties.name} w odległości ${Math.round(route.distance / 100) / 10} km (około ${Math.round(route.duration / 60)} min. drogi samochodem).`, latLng: this.getKnnLatLng(closest)});
        },

        getKnnLatLng(pointObj) {
            return [pointObj.lon, pointObj.lat]
        },

        closePicker() {
            this.locations = [];
        }
    },

    beforeDestroy() {
        const cookies = document.cookie.split(";");

        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i];
            const eqPos = cookie.indexOf("=");
            const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
        }
    }
};
</script>

<style lang="scss">
.marker-name {
    font-size: 15px;
    margin-bottom: 5px;
}

.marker-address {
    margin-bottom: 5px;
}

.marker-phone {
    margin-bottom: 7px;
}

.picked-location img {
    animation-name: bounce;
    animation-timing-function: ease-in-out;
    animation-delay: 1s;
    animation-duration: 1s;
    animation-iteration-count: 3;
}

a.marker-nav {
    display: block;
    background-color: #ed1c24;
    padding: 3px 12px;
    text-align: center;
    color: white !important;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition-duration: .2s;

    &:hover {
        background-color: #d23e42;
    }
}

@keyframes bounce {
    0% {
        transform: scale(1, 1) translateY(0);
    }
    10% {
        transform: scale(1.1, .9) translateY(0);
    }
    30% {
        transform: scale(.9, 1.1) translateY(-15px);
    }
    50% {
        transform: scale(1.05, .95) translateY(0);
    }
    57% {
        transform: scale(1, 1) translateY(-3px);
    }
    64% {
        transform: scale(1, 1) translateY(0);
    }
    100% {
        transform: scale(1, 1) translateY(0);
    }
}
</style>
