<template>
    <l-map
        ref="map"
        :zoom="zoom"
        :min-zoom="6"
        :max-zoom="17"
        :center="center"
        style="height: 500px; max-height: 70vh; width: 100%"
        :dragging="pc"
        :tap="pc"
        :options="{dragging: pc, tap: pc}"
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
                <img alt="Jesteś tu" title="Znaleziona lokalizacja" src="../assets/images/marker-icon-big.png">
            </l-icon>
        </l-marker>
        <l-geo-json :geojson="geojson" :visible="geojsonVisible"></l-geo-json>
    </l-map>
</template>

<script>
import { LGeoJson, LIcon, LMap, LMarker, LPopup, LTileLayer } from 'vue2-leaflet';
import Vue2LeafletMarkerCluster from 'vue2-leaflet-markercluster';
import { polygon } from 'leaflet';

export default {
    name: "LeafletMap",
    components: {
        LMap,
        LTileLayer,
        LMarker,
        LPopup,
        LIcon,
        LGeoJson,
        VMarkerCluster: Vue2LeafletMarkerCluster,
    },
    props: {
        zoom: Number,
        center: Array,
        markers: Array,
        pickedLocation: Array,
        geojson: Object,
        geojsonVisible: Boolean,
        token: String,
        flyToObj: Object,
        pc: Boolean,
    },
    data() {
        return {
            url: 'https://api.mapbox.com/styles/v1/mapbox/streets-v8/tiles/{z}/{x}/{y}?',
            attribution: '© <a href="https://apps.mapbox.com/feedback/" target="_blank">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
            isMounted: false,
        };
    },
    computed: {
        getTilesUrl() {
            return `${ this.url }${ this.token }`;
        },
        getPLPolygon() {
            return polygon([[56.752, 9.536], [47.174, 8.789], [47.159, 29.729], [56.740, 29.311], [56.752, 9.536]]);
        },
    },
    mounted() {
        this.$refs.map.mapObject.setMaxBounds(this.getPLPolygon.getBounds());
        setTimeout(() => {
            document.querySelector('a[href="https://leafletjs.com"]').setAttribute('target', '_blank');
        });
        this.isMounted = true;
    },
    watch: {
        "flyToObj.bounds"() {
            if (this.isMounted) {
                this.$refs.map.mapObject.flyToBounds(this.flyToObj.bounds, this.flyToObj.options);
            }
        },
    },
};
</script>

<style lang="scss" scoped>
@import '../styles/_LeafletMap.scss';
</style>
