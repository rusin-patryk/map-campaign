<template>
    <div class="mapbox-container">
        <l-map
            ref="map"
            :zoom="zoom"
            :min-zoom="6"
            :center="center"
            style="height: 600px; width: 100vw"
        >
            <l-tile-layer :url="url" :attribution="attribution" />
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
                        <strong>Jesteś tutaj!</strong>
                    </div>
                </l-popup>
                <l-icon :icon-size="[64, 64]" :icon-anchor="[32, 60]" className="picked-location">
                    <img alt="Jesteś tu" title="Jesteś tu" src="./../assets/marker-icon-big.png">
                </l-icon>
            </l-marker>
        </l-map>
        <hr>
        <input v-model="formData.postalCode" placeholder="XX-XXX" v-mask="'##-###'" />
        <input v-model="formData.city" placeholder="Miasto" />
        <button type="button" @click="searchLocation(formData.postalCode, formData.city)">Szukaj</button>
        <hr>
        <div v-if="results.length">
            <div v-for="(location, index) in results"
                 :key="index"
                 @click="centerOnLocation(location.point.coordinates)"
                 style="cursor: pointer">
                {{ index + 1 }}. {{ location.address.locality }}, powiat: {{ location.address.adminDistrict2 }}, woj.: {{ location.address.adminDistrict }}
            </div>
        </div>
    </div>
</template>

<script>
import { geoJson, latLngBounds } from 'leaflet';
import { LIcon, LMap, LMarker, LPopup, LTileLayer } from 'vue2-leaflet';
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
        VMarkerCluster: Vue2LeafletMarkerCluster,
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
            url: 'https://api.maptiler.com/maps/streets/{z}/{x}/{y}.png?key=wAfXyhTfSTvxebJ4AAAe',
            markers: markers,
            results: [],
            attribution: '<a href="https://www.maptiler.com/copyright/" target="_blank">© MapTiler</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">© OpenStreetMap contributors</a>',
        };
    },

    computed: {
        getGeoJSONData() {
            const points = [];
            markers.forEach((element) => {
                points.push({
                    type: "Point",
                    coordinates: [element.lat, element.lng],
                });
            });

            return geoJson(points);
        }
    },

    methods: {
        async searchLocation(postalCode, city) {
            if (postalCode || city) {
                this.pickedLocation = [];
                axios.get(`https://dev.virtualearth.net/REST/v1/Locations?countryRegion=PL${ city ? `&locality=${ city }` : '' }${ postalCode ? `&postalCode=${ postalCode }` : '' }&includeNeighborhood=0&maxResults=10&key=ArHNDlIpMt7aDoUFGXfHnThrf-M0m6-863pnM-7nBJJImEVH4VzQ74j2gXx2B17u`)
                    .then(response => {
                        console.log(response.data.resourceSets[0].resources);
                        this.results = response.data.resourceSets[0].resources;
                        if (this.results.length === 1) {
                            this.centerOnLocation(this.results[0].point.coordinates);
                        }
                    });
            }
        },

        centerOnLocation(latLng) {
            this.pickedLocation = latLng;
            const closest = leafletKnn(this.getGeoJSONData).nearest(latLng, 2);
            this.$refs.map.mapObject.flyToBounds(latLngBounds([latLng, this.getKnnLatLng(closest[0]), this.getKnnLatLng(closest[1])]), {padding: [60, 60], maxZoom: 15});
        },

        getKnnLatLng(pointObj) {
            return [pointObj.lon, pointObj.lat]
        },
    },
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
    animation-delay: 3s;
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
